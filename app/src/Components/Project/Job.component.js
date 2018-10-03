import React, {Component} from 'react';
import ProjectStore from "../../Stores/Project.store";
import ProjectConstants from "../../Constants/Project.constants"
import ProjectActions from '../../Actions/Project.actions';
import {DragDropContext} from 'react-dnd';
import {default as TouchBackend} from 'react-dnd-touch-backend';


import AdvancedDragLayer from './DragLayer/AdvancedDragLayer.component'
import env from "../../Constants/Env.constants";
import RowWrapperComponent from "./Row/RowWrapper.component";
import SplitComponent from "./Split/Split.component";
import ToolbarComponent from "./Toolbar/Toolbar.component";
import HTML5Backend from "react-dnd-html5-backend";

class JobComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
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
            mergeStatus: false,
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
            }
        };

        ProjectActions.setJobID(this.props.match.params.jobID)

    }

    static getDerivedStateFromProps(props, state) {

        return null;
    }

    shouldComponentUpdate(nextProps, nextState) {

        return true;
    }

    getSnapshotBeforeUpdate(prevProps, prevState) {

        return null;
    }

    componentDidUpdate(prevProps, prevState, snapshot) {

    }


    componentDidCatch() {

    }

    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.SEGMENT_TO_SPLIT, this.setSegmentToSplit);
        ProjectStore.addListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.addListener(ProjectConstants.MERGE_STATUS, this.setMergeStatus);
        ProjectStore.addListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
        ProjectActions.getSegments(this.props.match.params.jobID, this.props.match.params.jobPassword);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.SEGMENT_TO_SPLIT, this.setSegmentToSplit);
        ProjectStore.removeListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.removeListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
        ProjectStore.removeListener(ProjectConstants.MERGE_STATUS, this.setMergeStatus);
    }

    render() {
        return (
            <div className="align-project">
                <div className="ui container">
                    <div id="scroll-area">
                        {this.renderItems(this.state.job.rows)}
                    </div>
                    {/*<AdvancedDragLayer/>*/}
                    {this.state.splitModalStatus &&
                    <SplitComponent segment={this.state.segmentToSplit} jobConf={this.state.job.config}
                                    inverseSegmentOrder={this.state.job.rowsDictionary[this.state.segmentToSplit.type][this.state.segmentToSplit.order]}/>}
                    <ToolbarComponent/>
                </div>
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

    setRows = (job) => {
        let rows = [];
        let deletes = [];
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
            }
        });

        if (deletes.length > 0) {
            setTimeout(() => {
                ProjectActions.deleteEmptyRows(deletes);
            }, 0);

        }

        previousJob.rows = rows;
        previousJob.rowsDictionary = rowsDictionary;
        this.setState({
            job: previousJob
        })
    };

    renderItems(array) {
        let values = [];
        //if we can have complex regular for enable drag&drop use this var
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
                                                 mergeStatus={this.state.mergeStatus}
                                                 row={row}/>);
                return row;
            });
        }
        return values;
    }

    setMergeStatus = (status) => {
        this.setState({
            mergeStatus: status
        })
    };

    storeSelection = (selection) => {
        this.setState({
            selection: selection
        })
    }
}

export default DragDropContext(HTML5Backend)(JobComponent);