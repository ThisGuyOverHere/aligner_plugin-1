import React, {Component} from 'react';
import ProjectStore from "../../Stores/Project.store";
import ProjectConstants from "../../Constants/Project.constants"
import ProjectActions from '../../Actions/Project.actions';
import {DragDropContext} from 'react-dnd';
import RowWrapperComponent from "./Row/RowWrapper.component";
import SplitComponent from "./Split/Split.component";
import AdvancedDragLayer from "./DragLayer/AdvancedDragLayer.component.js";
import HTML5Backend from 'react-dnd-html5-backend';
import VirtualList from 'react-tiny-virtual-list';
import {syncWithBackend} from "../../Helpers/SystemUtils.helper";
import ExportModal from "../Shared/ExportModal/ExportModal.component";
import SystemConstants from "../../Constants/System.constants";
import SystemStore from "../../Stores/System.store";
import SystemActions from "../../Actions/System.actions";
import {httpConfig} from "../../HttpRequests/System.http";


class JobComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            statusExportModal: false,
            segmentToSplit: {},
            job: {
                config: {
                    password: this.props.match.params.password,
                    id: this.props.match.params.jobID
                },
                rows: [],
                rowsDictionary: {
                    source: {},
                    target: {}
                }
            },
            animateRowToOrder: {
                type: null,
                order: null,
                yCoord: null
            },
            splitModalStatus: false,
            inSync: false,
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
            googleUserImage: ''
        };

        this.elementsRef = {};
        this.virtualList = null;
        ProjectActions.setJobID(this.props.match.params.jobID,this.props.match.params.password)

    }

    componentDidMount() {
        SystemActions.checkUserStatus();
        SystemStore.addListener(SystemConstants.USER_STATUS, this.userStatus);
        SystemStore.addListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
        ProjectStore.addListener(ProjectConstants.SEGMENT_TO_SPLIT, this.setSegmentToSplit);
        ProjectStore.addListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.addListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
        ProjectActions.getSegments(this.props.match.params.jobID, this.props.match.params.password);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.USER_STATUS, this.userStatus);
        SystemStore.removeListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
        ProjectStore.removeListener(ProjectConstants.SEGMENT_TO_SPLIT, this.setSegmentToSplit);
        ProjectStore.removeListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.removeListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
    }

    setStatusExportModal = (status) => {
        this.setState({
            statusExportModal: status
        })
    };

    render() {
        const data = this.renderItems(this.state.job.rows);
        let classes = ['align-project'];
        if (this.state.inSync) {
            classes.push('inSync');
        }
        return (
            <div className={classes.join(" ")}>
                {this.state.statusExportModal &&
                <ExportModal
                    user = {this.state.user}
                    image = {this.state.googleUserImage}
                />}
                <VirtualList
                    ref={(instance) => {
                        this.virtualList = instance;
                    }}
                    width='100%'
                    height='calc(100vh - 108px)'
                    overscanCount={10}
                    itemCount={data.length}

                    itemSize={(index) => {

                        let source = document.createElement('p');
                        source.style.width = "437px";
                        source.style.fontSize = "16px";
                        source.innerHTML = this.state.job.rows[index].source.content_clean;
                        document.getElementById('hiddenHtml').appendChild(source);
                        const sourceHeight = source.getBoundingClientRect().height;

                        let target = document.createElement('p');
                        target.style.width = "437px";
                        target.style.fontSize = "16px";
                        target.innerHTML = this.state.job.rows[index].target.content_clean;
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
                />,
                <AdvancedDragLayer/>
                {this.state.splitModalStatus &&
                <SplitComponent segment={this.state.segmentToSplit} jobConf={this.state.job.config}
                                inverseSegmentOrder={this.state.job.rowsDictionary[this.state.segmentToSplit.type][this.state.segmentToSplit.order]}/>}
            </div>
        );
    }

    setSegmentToSplit = (segment) => {
        if (segment) {
            this.setState({
                segmentToSplit: segment,
                inverseOrder: this.state.job.rowsDictionary[segment.type][segment.order],
                splitModalStatus: true
            });
        } else {
            this.setState({
                segmentToSplit: null,
                splitModalStatus: false
            });
        }

    };

    setRows = (job, syncAPI) => {
        let rows = [];
        let deletes = [];
        let matches = [];
        let previousJob = this.state.job;
        let rowsDictionary = {
            source: {},
            target: {}
        };
        job.source.map((e, index) => {
            rowsDictionary.source[e.order] = job.target[index].order;
            rowsDictionary.target[job.target[index].order] = e.order;
            //todo: send API for remove empty/empty from DB
            if (e.content_clean || job.target[index].content_clean) {
                rows.push({
                    source: e,
                    target: job.target[index]
                });
            } else {
                deletes.push(index);
                matches.push({
                    type: 'source',
                    order: e.order
                });
                matches.push({
                    type: 'target',
                    order: job.target[index].order
                });
            }
        });

        previousJob.rows = rows;
        previousJob.rowsDictionary = rowsDictionary;


        let inSync = false;
        if (syncAPI) {
            inSync = true;
            syncWithBackend(syncAPI,()=>{
                if (deletes.length > 0) {
                    setTimeout(() => {
                        ProjectActions.deleteEmptyRows(deletes,matches);
                    }, 0);

                }
                this.setState({
                    inSync: false
                })
            });
        }else{
            if (deletes.length > 0) {
                setTimeout(() => {
                    ProjectActions.deleteEmptyRows(deletes,matches);
                }, 0);

            }
        }


        this.setState({
            job: previousJob,
            inSync: inSync
        })
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
                values.push(<RowWrapperComponent key={index}
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
    userStatus = (status,fromLogin, image, error) => {
        this.setState({
            user: status,
            googleUserImage: image
        })
    };
}

export default DragDropContext(HTML5Backend)(JobComponent);

