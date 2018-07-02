import React, {Component} from 'react';
import ProjectsStore from "../../Stores/Projects.store";
import ProjectsConstants from "../../Constants/Projects.constants"
import ProjectsActions from '../../Actions/Projects.actions';

class ProjectComponent extends Component {
    constructor(props) {
        super(props);

        this.state = {
            project: {
                config: {
                    password: this.props.match.params.password,
                    id: this.props.match.params.projectID
                }
            }
        };

        this._test = this._test.bind(this);
    }
    static getDerivedStateFromProps(props, state){
        //console.log(props,state);
        return null
    }
    _test(){
        console.log('funziona');
    }

    componentDidMount() {
        ProjectsStore.addListener(ProjectsConstants.GET_PROJECTS, this._test);
        ProjectsActions.getProjects();
    }


    componentWillUnmount() {
        ProjectsStore.removeListener(ProjectsConstants.GET_PROJECTS, this._test);
    }


    render() {
        return (
            <div>
                <p>Project</p>
            </div>
        );
    }
}

export default ProjectComponent;