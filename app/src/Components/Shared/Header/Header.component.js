import React, {Component} from 'react';
import { Link } from "react-router-dom";
import PropTypes from "prop-types";
import {textEllipsisCenter} from "../../../Helpers/SystemUtils.helper";
import User from "./User/User.component";
import Mismatch from "./Mismatch/Mismatch.component";
import Export from "./Export/Export.component";

class HeaderComponent extends Component {

    static propTypes = {
        match: PropTypes.shape({
            params: PropTypes.shape({
                jobID: PropTypes.string
            })
        }).isRequired,
        user: PropTypes.oneOfType([PropTypes.bool,PropTypes.object])
    };

    constructor(props) {
        super(props);
        const jobID = (this.props.match
            && this.props.match.params
            && this.props.match.params.jobID) ? this.props.match.params.jobID : null;
        this.state = {
            pName: '',
            projectTitle: 'Sample title for test header ellipsis at center',
            sourceLang: 'en-US',
            targetLang: 'it-IT',
            job: {
                name: null,
                id: jobID,
                segments: null
            },
            loggedIn: false,
        };
    }

     static getDerivedStateFromProps(nextProps, prevState) {
         if(nextProps.match.params && nextProps.match.params.jobID){
             prevState.job.id = nextProps.match.params.jobID;
         }else{
             prevState.job.id = null;
         }
        return prevState;
    };

    renderHtmlNavigation = () => {
        if(this.state.job.id){
            return <ul className="aligner-nav-log" role="navigation">
                <li>
                    <Link to="/">
                        <div id="logo"></div>
                    </Link>
                    <div id="final_title">
                        {textEllipsisCenter(this.state.projectTitle)}
                    </div>
                </li>
                <li>
                    <div id="source_to_target">
                        <span id="source">{this.state.sourceLang}</span>
                        >
                        <span id="source">{this.state.targetLang}</span>
                    </div>
                </li>
                <li>
                    {/*<Mismatch />*/}
                    <Export/>
                </li>
                <li>
                    <User user={this.props.user}/>
                </li>
            </ul>;
        } else {
            return <ul className="aligner-nav-nolog" role="navigation">
                <Link to="/">
                    <div id="logo"></div>
                </Link>
                <User user={this.props.user}/>
            </ul>
        }
    };

    render() {
        return (
            <div id="header">
                { this.renderHtmlNavigation() }
            </div>
        );
    }

}
export default HeaderComponent;