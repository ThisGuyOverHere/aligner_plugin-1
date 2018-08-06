import React, {Component} from 'react';
import ProjectStore from "../../Stores/Project.store";
import ProjectConstants from "../../Constants/Project.constants"
import ProjectActions from '../../Actions/Project.actions';
import RowComponent from './Row/Row.component';
import SegmentComponent from './Row/Segment/Segment.component';
import {DragDropContext} from 'react-dnd';
import HTML5Backend from 'react-dnd-html5-backend';
import AdvancedDragLayer from './DragLayer/AdvancedDragLayer.component'
import env from "../../Constants/Env.constants";

class ProjectComponent extends Component {
    constructor(props) {
        super(props);

        this.state = {
            algorithm: env.alignAlgorithmDefaultVersion,
            project: {
                config: {
                    password: this.props.match.params.password,
                    id: this.props.match.params.jobID
                },
                rows: []
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


    setRows = (rows) => {
        this.setState({
            project: {
                rows: rows
            }
        })
    };

    changeAlgorithmVersion = (e) => {
        ProjectActions.getRows(this.props.match.params.jobID, this.props.match.params.jobPassword, e.target.value);

        this.setState({
            algorithm: e.target.value
        });
    };

    renderItems(array) {
        let values = [];
        if (array.length > 0) {
            array.map((row, index) => {
                values.push(<RowComponent key={index} index={index} row={row}>
                    <SegmentComponent type={0}
                                      value={row.source.content}
                                      order={row.order}/>
                    <SegmentComponent type={1}
                                      value={row.target.content}
                                      order={row.order}/>
                </RowComponent>);
                return row;
            });
        }
        return values;
    }

    render() {
        let algorithmElements = [];
        env.alignAlgorithmAllVersions.map(e => {
            const checked = (this.state.algorithm == e) ? true : false;
            algorithmElements.push(<option value={e} selected={checked} >Algorithm V{e}</option>);
        });
        return (
            <div className="align-project">
                <div className="ui container">
                    <select name="algorithm" id="algorithm" onChange={this.changeAlgorithmVersion}>
                        {algorithmElements}
                    </select>
                </div>

                <div className="ui container">
                    {this.renderItems(this.state.project.rows)}
                    <AdvancedDragLayer/>
                </div>
            </div>
        );
    }

    componentDidCatch() {

    }

    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectActions.getRows(this.props.match.params.jobID, this.props.match.params.jobPassword);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.RENDER_ROWS, this.setRows);
    }


}

export default DragDropContext(HTML5Backend)(ProjectComponent);