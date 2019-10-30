import React, {Component} from 'react';
import {Link} from "react-router-dom";
import PropTypes from "prop-types";
import User from "./User/User.component";
import Export from "./Export/Export.component";
import {httpGetAlignmentInfo} from "../../../HttpRequests/Alignment.http";
import ProjectStore from "../../../Stores/Project.store";
import ProjectConstants from "../../../Constants/Project.constants";
import ProjectActions from "../../../Actions/Project.actions";
import Truncate from "../Truncate/Truncate.component";
/*import TruncateString from 'react-truncate-string'
import Truncate from "../Truncate/Truncate.component";*/

class HeaderComponent extends Component {

    static propTypes = {
        hideToolbar: PropTypes.bool,
        match: PropTypes.shape({
            params: PropTypes.shape({
                jobID: PropTypes.string,
                jobPassword: PropTypes.string
            })
        }).isRequired,
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
        image: PropTypes.string
    };

    constructor(props) {
        super(props);
        this.state = {
            pName: '',
            projectTitle: '',
            sourceLang: '',
            targetLang: '',
            job: {
                config: {
                    password: this.props.match.params.password,
                    id: this.props.match.params.jobID
                },
                name: null,
                segments: null
            },
            loggedIn: false,
            statusEmptyModal: false,
            jobError: false
        };
    }

    static getDerivedStateFromProps(nextProps, prevState) {
        if (nextProps.match.params && nextProps.match.params.jobID) {
            prevState.job.config.id = nextProps.match.params.jobID;
            prevState.job.config.password = nextProps.match.params.jobPassword;
        } else {
            prevState.job.config.id = null;
            prevState.job.config.password = null;
        }
        return prevState;
    };

    componentDidMount() {
        if (this.props.match.params.jobID) {
            this.getInfo();
        }
        ProjectStore.addListener(ProjectConstants.JOB_ERROR, this.getJobError);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.JOB_ERROR, this.getJobError);
    }

    componentDidUpdate(prevProps) {
        if (this.props.match.params.jobID && this.props.match.params.jobID !== prevProps.match.params.jobID) {
            this.getInfo();
        }
    }

    getJobError = (error) => {
        this.setState({
            jobError: error
        })
    };

    renderHtmlNavigation = () => {
        const {projectTitle} = this.state;

        if (this.state.job.config.id && !this.state.jobError) {
            return <div>
                <ul className="aligner-nav-log" role="navigation">
                    <li>
                        <div id="logo">
                            <Link to="/">
                                <img src="./static/build/images/logo.png" alt=""/>
                            </Link>
                        </div>
                    </li>
                    <li>
                        {projectTitle && <Truncate title={projectTitle}/>}
                    </li>
                    <li/>
                    <li id={"source"}>
                        <span>{this.state.sourceLang}</span>
                    </li>

                    <li id={"to"}>
                        <span> &#8594; </span>
                    </li>

                    <li id={"target"}>
                        <span>{this.state.targetLang}</span>
                    </li>

                    <li id="export">
                        {/*<Mismatch />
                          to do: with pulling process completed,
                          pass disable property while align process will be completed
                        */}
                        <Export/>
                    </li>

                    <li>
                        <User image={this.props.image} user={this.props.user}/>
                    </li>
                </ul>

            </div>;
        } else {
            return <ul className="aligner-nav-nolog" role="navigation">
                <li>
                    <div id="logo">
                        <Link to="/">
                            <img src="./static/build/images/logo.png"/>
                        </Link>
                    </div>
                </li>
                <li className="return-to-matecat">
                    <a href="/" target="blank">Go to Matecat</a>
                </li>
                <li>
                    <User image={this.props.image} user={this.props.user}/>
                </li>
            </ul>
        }
    };

    render() {
        return (
            <div id="header">
                {this.renderHtmlNavigation()}
            </div>
        );
    }

    getInfo = () => {
        // get job info
        httpGetAlignmentInfo(this.props.match.params.jobID, this.props.match.params.password)
            .then(
                response => {
                    const info = response.data;
                    /*console.log(info);*/
                    if (info) {
                        this.setState({
                            projectTitle: info.job_name,
                            sourceLang: info.source_lang,
                            targetLang: info.target_lang,
                        });
                        ProjectActions.setJobInfo(info);
                    }
                }
            ).catch(
            error => {
                console.error(error);
            }
        );
    }

}

export default HeaderComponent;
