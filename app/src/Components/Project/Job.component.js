import React, {Component} from 'react';
import ProjectStore from "../../Stores/Project.store";
import ProjectConstants from "../../Constants/Project.constants"
import ProjectActions from '../../Actions/Project.actions';
import RowComponent from './Row/Row.component';
import {DragDropContext} from 'react-dnd';
import HTML5Backend from 'react-dnd-html5-backend';
import { default as TouchBackend } from 'react-dnd-touch-backend';



import AdvancedDragLayer from './DragLayer/AdvancedDragLayer.component'
import env from "../../Constants/Env.constants";
import RowWrapperComponent from "./Row/RowWrapper.component";

class JobComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
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
            selection: {
                source: {},
                target: {},
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
        ProjectStore.addListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.addListener(ProjectConstants.MERGE_STATUS, this.setMergeStatus);
        ProjectStore.addListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
        ProjectActions.getSegments(this.props.match.params.jobID, this.props.match.params.jobPassword);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.removeListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
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
                </div>
            </div>
        );
    }

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
        const enableDrag = this.state.selection.count === 0;
        if (array.length > 0) {
            array.map((row, index) => {
                const selection = {
                    source: !!this.state.selection.source[row.source.order],
                    target: !!this.state.selection.target[row.target.order],
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

    storeSelection = (selection) =>{
        this.setState({
            selection: selection
        })
    }
}

export default DragDropContext(TouchBackend({
    enableMouseEvents: true,
    touchSlop: 5
}))(JobComponent);