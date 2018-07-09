import React, {Component} from 'react';
import ProjectsStore from "../../Stores/Projects.store";
import ProjectsConstants from "../../Constants/Projects.constants"
import ProjectsActions from '../../Actions/Projects.actions';
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
                    id: this.props.match.params.projectID
                },
                rows: [
                    {source: 1, target: 1},
                    {source: 2, target: 4},
                    {source: 3, target: 3},
                    {source: 4, target: 5},
                    {source: 5, target: 2},
                    {source: 6, target: null}
                ]
            }
        };

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


    _test = () => {
        console.log('Arrow function and Flux init');
    };

    renderItems(array) {
        let values = [];
        if (array.length > 0) {
            array.map((item, pos) => {
                values.push(<RowComponent key={pos}>
                    <SegmentComponent type={0}
                             value={item.source}
                             position={pos}/>
                    <SegmentComponent type={1}
                             position={pos}
                             value={item.target}/>
                </RowComponent>);
                return item
            });
        }
        return values;
    }

    render() {
        return (
            <div>
                {this.renderItems(this.state.project.rows)}
            </div>
        );
    }

    componentDidCatch() {

    }

    componentDidMount() {
        ProjectsStore.addListener(ProjectsConstants.GET_PROJECTS, this._test);
        ProjectsActions.getProjects();
    }

    componentWillUnmount() {
        ProjectsStore.removeListener(ProjectsConstants.GET_PROJECTS, this._test);
    }


}

export default DragDropContext(HTML5Backend)(ProjectComponent);