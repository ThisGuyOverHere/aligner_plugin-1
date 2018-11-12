import React, {Component} from 'react';
import PropTypes from "prop-types";
import JobComponent from "./Job/Job.component";
import ProjectActions from "../../Actions/Project.actions";
import ToolbarComponent from "./Toolbar/Toolbar.component";

class AlignComponent extends Component {
    static propTypes = {
        match: PropTypes.shape({
            params: PropTypes.shape({
                password: PropTypes.any,
                jobID: PropTypes.any
            })

        })
    };
    constructor(props) {
        super(props);
        this.state = {
            job: {
                config: {
                    password: this.props.match.params.password,
                    id: this.props.match.params.jobID
                },
            }
        };
        ProjectActions.setJobID(this.props.match.params.jobID, this.props.match.params.password)
    }


    componentDidMount() {
        ProjectActions.getSegments(this.props.match.params.jobID, this.props.match.params.password);
    }

    componentWillUnmount() {
    }

    render() {
        return (
            <div id="Align">
                {this.state.job.config && <ToolbarComponent jobConf={this.state.job.config}/>}
                <JobComponent match={this.props.match}/>
            </div>
        );
    }

    

}

export default AlignComponent;
