import React, {Component} from 'react';
import ProjectStore from "../../../Stores/Project.store";
import ProjectConstants from "../../../Constants/Project.constants"
import {DragDropContext} from 'react-dnd';
import RowWrapperComponent from "./Row/RowWrapper.component";
import SplitComponent from "./Split/Split.component";
import AdvancedDragLayer from "./DragLayer/AdvancedDragLayer.component.js";
import HTML5Backend from 'react-dnd-html5-backend';
import VirtualList from 'react-tiny-virtual-list';
import ExportModal from "../../Shared/ExportModal/ExportModal.component";
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";
import SystemActions from "../../../Actions/System.actions";
import PropTypes from "prop-types";


class JobComponent extends Component {
    static propTypes = {
        job: PropTypes.shape({
            config: PropTypes.shape({
                password: PropTypes.any,
                id: PropTypes.any
            }),
            rows: PropTypes.array,
            rowsDictionary: PropTypes.any
        }),
        inSync: PropTypes.bool
    };

    constructor(props) {
        super(props);
        this.state = {
            statusExportModal: false,
            segmentToSplit: {},
            animateRowToOrder: {
                type: null,
                order: null,
                yCoord: null
            },
            splitModalStatus: false,
            selection: {
                source: {
                    count: 0,
                    list: [],
                    map: {}
                },
                target: {
                    count: 0,
                    list: [],
                    map: {}
                },
                count: 0
            },
            user: false,
            googleUserImage: '',
            occurrencesList: [],
            scrollToSegment: 0,
            window: {
                width: 0,
                height: 0,
            }
        };

        this.elementsRef = {};
        this.virtualList = null;
    }

    componentDidMount() {
        SystemActions.checkUserStatus();
        this.updateWindowDimensions();
        window.addEventListener('resize', this.updateWindowDimensions);
        SystemStore.addListener(SystemConstants.USER_STATUS, this.userStatus);
        SystemStore.addListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
        ProjectStore.addListener(ProjectConstants.SCROLL_TO_SEGMENT, this.setScrollToSegment);
        ProjectStore.addListener(ProjectConstants.SEGMENT_TO_SPLIT, this.setSegmentToSplit);
        ProjectStore.addListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
        ProjectStore.addListener(ProjectConstants.SEARCH_RESULTS, this.onSearchEvent);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.USER_STATUS, this.userStatus);
        SystemStore.removeListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
        ProjectStore.removeListener(ProjectConstants.SCROLL_TO_SEGMENT, this.setScrollToSegment);
        ProjectStore.removeListener(ProjectConstants.SEGMENT_TO_SPLIT, this.setSegmentToSplit);
        ProjectStore.removeListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
        ProjectStore.removeListener(ProjectConstants.SEARCH_RESULTS, this.onSearchEvent);
        window.removeEventListener('resize', this.updateWindowDimensions);

    }

    render() {
        const data = this.renderItems(this.props.job.rows);
        let classes = ['align-project'];
        if (this.props.inSync) {
            classes.push('inSync');
        }
        return (
            <div className={classes.join(" ")}>
                {this.state.statusExportModal &&
                <ExportModal
                    user={this.state.user}
                    image={this.state.googleUserImage}
                />}
                {data.length > 0 &&
                <VirtualList
                    ref={(instance) => {
                        this.virtualList = instance;
                    }}
                    width={this.state.window.width}
                    height={this.state.window.height-112}
                    overscanCount={2}
                    itemCount={data.length}
                    scrollToIndex={this.state.scrollToSegment}
                    estimatedItemSize={80}
                    scrollToAlignment="center"
                    itemSize={(index) => {

                        let source = document.createElement('p');
                        source.style.width = "437px";
                        source.style.fontSize = "16px";
                        source.innerHTML = this.props.job.rows[index].source.content_clean;
                        document.getElementById('hiddenHtml').appendChild(source);
                        const sourceHeight = source.getBoundingClientRect().height;

                        let target = document.createElement('p');
                        target.style.width = "437px";
                        target.style.fontSize = "16px";
                        target.innerHTML = this.props.job.rows[index].target.content_clean;
                        document.getElementById('hiddenHtml').appendChild(target);
                        const targetHeight = target.getBoundingClientRect().height;

                        document.getElementById('hiddenHtml').innerHTML = "";
                        return Math.max(sourceHeight, targetHeight) + 64
                    }}
                    renderItem={({index, style}) =>
                        <div key={index} style={style} ref={(el) => {
                            this.elementsRef[index] = el;
                        }
                        }>
                            {data[index]}
                        </div>
                    }
                />}
                <AdvancedDragLayer/>
                {this.state.splitModalStatus &&
                <SplitComponent segment={this.state.segmentToSplit} jobConf={this.props.job.config}
                                inverseSegmentOrder={this.props.job.rowsDictionary[this.state.segmentToSplit.type][this.state.segmentToSplit.order]}/>}
            </div>
        );
    }

    setStatusExportModal = (status) => {
        this.setState({
            statusExportModal: status
        })
    };
    updateWindowDimensions = () => {
        let data = {};

        data.width = window.innerWidth;
        data.height = window.innerHeight;

        this.setState({
            window: data
        })
    };
    setScrollToSegment = (index) =>{
        console.log(index);
        this.setState({
            scrollToSegment: index
        })
    };
    setSegmentToSplit = (segment) => {
        if (segment) {
            this.setState({
                segmentToSplit: segment,
                inverseOrder: this.props.job.rowsDictionary[segment.type][segment.order],
                splitModalStatus: true
            });
        } else {
            this.setState({
                segmentToSplit: null,
                splitModalStatus: false
            });
        }

    };


    renderItems(array) {
        let values = [];
        const enableDrag = true;
        if (array.length > 0) {
            array.map((row, index) => {
                const selection = {
                    source: !!this.state.selection.source.map[row.source.order],
                    target: !!this.state.selection.target.map[row.target.order],
                    count: this.state.selection.count
                };
                values.push(<RowWrapperComponent
                    key={index}
                    index={index}
                    enableDrag={enableDrag}
                    selection={selection}
                    row={row}/>);
                return row;
            });
        }
        return values;
    }

    storeSelection = (selection) => {
        this.setState({
            selection: selection
        })
    };

    // export component
    userStatus = (status, fromLogin, image, error) => {
        this.setState({
            user: status,
            googleUserImage: image
        })
    };

    onSearchEvent = (search) => {
        console.log(search)
        if(search.occurrencesList.toString()){
            this.setState({
                occurrencesList: search.occurrencesList,
                scrollToSegment: search.occurrencesList[search.featuredSearchResult].index
            });
        }
    }
}

export default DragDropContext(HTML5Backend)(JobComponent);

