import React, {Component} from 'react';
import ProjectStore from "../../Stores/Project.store";
import ProjectConstants from "../../Constants/Project.constants"
import ProjectActions from '../../Actions/Project.actions';
import RowComponent from './Row/Row.component';
import SegmentComponent from './Row/Segment/Segment.component';
import {DragDropContext} from 'react-dnd';
import MouseBackEnd from 'react-dnd-mouse-backend'

import AdvancedDragLayer from './DragLayer/AdvancedDragLayer.component'
import env from "../../Constants/Env.constants";
import SplitComponent from "./Split/Split.component";

class JobComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            segmentToSplit: {},
            algorithm: env.alignAlgorithmDefaultVersion,
            job: {
                config: {
                    password: this.props.match.params.password,
                    id: this.props.match.params.jobID
                },
                rows: []
            },
            animateRowToOrder: {
                type: null,
                order: null,
                yCoord: null
            },
            mergeStatus: false,
            splitModalStatus: false,
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
        ProjectStore.addListener(ProjectConstants.SET_SPLIT_MODAL_STATUS, this.setStatusSplitModal);
        ProjectStore.addListener(ProjectConstants.SEGMENT_TO_SPLIT, this.setSegmentToSplit);
        ProjectStore.addListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.addListener(ProjectConstants.MERGE_STATUS, this.setMergeStatus);
        ProjectActions.getSegments(this.props.match.params.jobID, this.props.match.params.jobPassword);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.SET_SPLIT_MODAL_STATUS, this.setStatusSplitModal);
        ProjectStore.removeListener(ProjectConstants.SEGMENT_TO_SPLIT, this.setSegmentToSplit);
        ProjectStore.removeListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.removeListener(ProjectConstants.MERGE_STATUS, this.setMergeStatus);
    }

    render() {
        let algorithmElements = [];
        env.alignAlgorithmAllVersions.map(e => {
            algorithmElements.push(<option key={e} value={e}>Algorithm v{e}</option>);
        });
        return (
            <div className="align-project">
                <div className="ui container">
                    <div id="scroll-area">
                        {this.renderItems(this.state.job.rows)}
                    </div>
                    <AdvancedDragLayer/>
                    {this.state.splitModalStatus &&  <SplitComponent segment = {this.state.segmentToSplit}/>}
                </div>
            </div>
        );
    }

    setStatusSplitModal = (status) => {
        this.setState({
            splitModalStatus: status
        })
    };

    setSegmentToSplit = (segment) => {
        this.setState({
            segmentToSplit: segment,
        });
    };

    setRows = (job) => {
        let rows = [];
        job.source.map((e, index) => {
            rows.push({
                source: e,
                target: job.target[index]
            });
        });
        this.setState({
            job: {
                rows: rows
            }
        })
    };

    renderItems(array) {
        let values = [];
        if (array.length > 0) {
            array.map((row, index) => {
                values.push(<RowComponent key={index}
                                          index={index}
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
    }
}

export default DragDropContext(MouseBackEnd)(JobComponent);