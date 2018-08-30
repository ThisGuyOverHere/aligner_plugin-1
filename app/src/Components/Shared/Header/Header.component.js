import React, {Component} from 'react';
import { Link } from "react-router-dom";
import SystemActions from "../../../Actions/System.actions";
import PropTypes from "prop-types";
import {textEllipsisCenter} from "../../../Helpers/SystemUtils.helper";

class HeaderComponent extends Component {

    static propTypes = {
        match: PropTypes.shape({
            params: PropTypes.shape({
                jobID: PropTypes.string
            })
        }).isRequired,
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
    }

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
                    <div id="mini_align_nav">
                        <p id="aligned"> Mismatch 1 </p> / 5
                        <span>
                            <i className="icon angle up"></i>
                        </span>
                        <span>
                            <i className="icon angle down"></i>
                        </span>
                    </div>
                    <div id="export">
                        <button className="ui primary button">
                            <span>
                                Export
                                <i aria-hidden='true' className="upload icon"></i>
                            </span>
                        </button>
                    </div>
                    <div id="user">
                        <div className="ui user-nolog label" title="Login" onClick={this.openLogin}>
                            <i className="icon user"></i>
                        </div>
                    </div>
                </li>
            </ul>;
        } else {
            return <ul className="aligner-nav-nolog" role="navigation">
                <Link to="/">
                    <div id="logo"></div>
                </Link>
                <li id="user">
                    <div className="ui user-nolog label" title="Login" onClick={this.openLogin}>
                        <i className="icon user"></i>
                    </div>
                </li>
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


    openLogin = () =>{
        SystemActions.setLoginStatus(true)
    }

}
export default HeaderComponent;