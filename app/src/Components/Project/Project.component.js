import React, {Component} from 'react';
import ProjectStore from "../../Stores/Project.store";
import ProjectConstants from "../../Constants/Project.constants"
import ProjectActions from '../../Actions/Project.actions';
import RowComponent from './Row/Row.component';
import SegmentComponent from './Row/Segment/Segment.component';
import {DragDropContext} from 'react-dnd';
import HTML5Backend from 'react-dnd-html5-backend';

class ProjectComponent extends Component {
    constructor(props) {
        super(props);

        this.state = {
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

    renderItems(array) {
        let values = [];
        if (array.length > 0) {
            array.map((row,index) => {
                values.push(<RowComponent key={index}>
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
        return (
            <div className="ui container">
                {this.renderItems(this.state.project.rows)}
            </div>
        );
    }

    componentDidCatch() {

    }

    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectActions.getRows();
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.RENDER_ROWS, this.setRows);
    }


}

export default DragDropContext(HTML5Backend)(ProjectComponent);