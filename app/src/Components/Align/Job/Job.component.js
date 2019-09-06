import React, {Component} from 'react';
import ProjectStore from "../../../Stores/Project.store";
import ProjectConstants from "../../../Constants/Project.constants"
import {DragDropContext} from 'react-dnd';
import RowWrapperComponent from "./Row/RowWrapper.component";
import HTML5Backend from 'react-dnd-html5-backend';
import VirtualList from 'react-tiny-virtual-list';
import ExportModal from "../../Shared/ExportModal/ExportModal.component";
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";
import SystemActions from "../../../Actions/System.actions";
import PropTypes from "prop-types";
import HideComponent from "./HideRow/HideRow.component";
import SplitAlternative from "./SplitAlternative/SplitAlternative.component"

class JobComponent extends Component {
    static propTypes = {
        jobInfo: PropTypes.object,
        job: PropTypes.shape({
            config: PropTypes.shape({
                password: PropTypes.any,
                id: PropTypes.any
            }),
            rows: PropTypes.array,
            rowsDictionary: PropTypes.any,
            counters:PropTypes.shape({
                hideIndexesMap: PropTypes.array,
                misalignmentsIndexesMap: PropTypes.array
            }),
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
            scrollToSegment: null,
            search: {},
            window: {
                segmentContentWidth: "437px",
                width: 0,
                height: 0,
            },
            hideNavigatorData: null,
            misalignmentsNavigatorData: null
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
        ProjectStore.addListener(ProjectConstants.HIDE_SEGMENTS_NAVIGATOR, this.onHideNavigatorEvent);
        ProjectStore.addListener(ProjectConstants.MISALIGNMENT_NAVIGATOR, this.onMisalignmentsNavigatorEvent);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.USER_STATUS, this.userStatus);
        SystemStore.removeListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
        ProjectStore.removeListener(ProjectConstants.SCROLL_TO_SEGMENT, this.setScrollToSegment);
        ProjectStore.removeListener(ProjectConstants.SEGMENT_TO_SPLIT, this.setSegmentToSplit);
        ProjectStore.removeListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
        ProjectStore.removeListener(ProjectConstants.SEARCH_RESULTS, this.onSearchEvent);
        ProjectStore.removeListener(ProjectConstants.HIDE_SEGMENTS_NAVIGATOR, this.onHideNavigatorEvent);
        ProjectStore.removeListener(ProjectConstants.MISALIGNMENT_NAVIGATOR, this.onMisalignmentsNavigatorEvent);
        window.removeEventListener('resize', this.updateWindowDimensions);
    }

    componentDidUpdate() {
        if (this.state.scrollToSegment !== null) {
            this.setState({
                scrollToSegment: null
            })
        }
    }

    render() {
        const { job: {counters:{hideIndexesMap,misalignmentsIndexesMap}}} = this.props;
        const data = this.renderItems(this.props.job.rows);
        let classes = ['align-project'];
        if (this.props.inSync) {
            classes.push('inSync');
        }
        return (
            <div className={classes.join(" ")}>
                {this.state.statusExportModal && <ExportModal
                    user={this.state.user}
                    image={this.state.googleUserImage}
                    misAlignedSegments={misalignmentsIndexesMap.length}
                    hideSegments={hideIndexesMap.length}
                />}
                {data.length > 0 &&
                <VirtualList
                    ref={(instance) => {
                        this.virtualList = instance;
                    }}
                    width={this.state.window.width}
                    height={this.state.window.height - 160}
                    overscanCount={2}
                    itemCount={data.length}
                    scrollToIndex={this.state.scrollToSegment}
                    estimatedItemSize={80}
                    scrollToAlignment="center"
                    itemSize={(index) => {
                        let itemHeigth = 0;
                        if (+this.props.job.rows[index].source.hidden === 1 || +this.props.job.rows[index].target.hidden === 1) {
                            document.getElementById('hiddenHtml').innerHTML = "";
                            itemHeigth = 52
                        } else {
                            let source = document.createElement('p');
                            source.style.width = this.state.window.segmentContentWidth;
                            source.style.fontSize = "16px";
                            source.innerHTML = this.props.job.rows[index].source.content_clean;
                            document.getElementById('hiddenHtml').appendChild(source);
                            const sourceHeight = source.getBoundingClientRect().height;

                            let target = document.createElement('p');
                            target.style.width = this.state.window.segmentContentWidth;
                            target.style.fontSize = "16px";
                            target.innerHTML = this.props.job.rows[index].target.content_clean;
                            document.getElementById('hiddenHtml').appendChild(target);
                            const targetHeight = target.getBoundingClientRect().height;

                            document.getElementById('hiddenHtml').innerHTML = "";
                            itemHeigth = Math.max(sourceHeight, targetHeight) + 64
                        }
                        return itemHeigth;
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
                {/*<AdvancedDragLayer/>*/}
                {this.state.splitModalStatus &&
                    <SplitAlternative segment={this.state.segmentToSplit} jobConf={this.props.job.config}
                                             inverseSegmentOrder={this.props.job.rowsDictionary[this.state.segmentToSplit.type][this.state.segmentToSplit.order]}/>
                }
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

        if (window.innerWidth < 992) {
            data.segmentContentWidth = "235px";
        } else if (window.innerWidth < 1200) {
            data.segmentContentWidth = "340px";
        } else {
            data.segmentContentWidth = "437px";
        }

        data.width = window.innerWidth;
        data.height = window.innerHeight;

        this.setState({
            window: data
        })
    };
    setScrollToSegment = (index) => {
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
        const { job: {counters:{hideIndexesMap,misalignmentsIndexesMap}}} = this.props;
        const {hideNavigatorData,misalignmentsNavigatorData} = this.state;
        const misalignedNav = misalignmentsNavigatorData ? misalignmentsNavigatorData.realRowIndex : null;

        if (array.length > 0) {
            array.map((row, index) => {
                const selection = {
                    source: !!this.state.selection.source.map[row.source.order],
                    target: !!this.state.selection.target.map[row.target.order],
                    count: this.state.selection.count
                };
                if (+row.source.hidden === 1 || +row.target.hidden === 1) {
                    values.push(<HideComponent
                        key={index}
                        index={index}
                        enableDrag={enableDrag}
                        selection={selection}
                        isInHideNavigator={hideIndexesMap.includes( hideNavigatorData ? hideNavigatorData.realRowIndex : null )}
                        selectedInNavigator={hideNavigatorData ? hideNavigatorData.realRowIndex === index  : null}
                        row={row}
                    />)
                } else {
                    values.push(<RowWrapperComponent
                        search={this.state.search}
                        key={index}
                        index={index}
                        jobInfo={this.props.jobInfo}
                        enableDrag={enableDrag}
                        selection={selection}
                        isInMisalignedNavigator={misalignedNav ? misalignmentsIndexesMap.includes( index ) : null}
                        selectedInNavigator={misalignmentsNavigatorData ? misalignmentsNavigatorData.realRowIndex === index  : null}
                        row={row}/>);
                }
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
        const scrollToSegment = search.occurrencesList.length > 0 ? search.occurrencesList[search.featuredSearchResult].index : null;
        this.setState({
            search: search,
            scrollToSegment: scrollToSegment
        });
    };

    onHideNavigatorEvent = (hideNavigatorData) => {
        console.log('in navigator interceptor: ',hideNavigatorData);
        this.setState({
            hideNavigatorData: hideNavigatorData,
            scrollToSegment: hideNavigatorData.realRowIndex
        });
    };

    onMisalignmentsNavigatorEvent = (misalignmentsNavigatorData) => {
        console.log('in navigator interceptor: ', misalignmentsNavigatorData);
        this.setState({
            misalignmentsNavigatorData: misalignmentsNavigatorData,
            scrollToSegment: misalignmentsNavigatorData.realRowIndex
        });
    }
}

export default DragDropContext(HTML5Backend)(JobComponent);

