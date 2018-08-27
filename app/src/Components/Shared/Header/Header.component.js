import React, {Component} from 'react';
import {Link} from "react-router-dom";
import env from "../../../Constants/Env.constants";

class HeaderComponent extends Component {

    static propTypes = {};

    constructor() {
        super();
        this.state = {
            job: undefined,
            pName: '',
            projectTitle: 'Sample title for test header ellipsis at center',
            sourceLang: 'en-US',
            targetLang: 'it-IT',
            alignmentStarted: true
        }
    }

    titleEllipsisCenter = () => {
        let str = this.state.projectTitle;
        if(str.length > 30){
            return str.substr(0, 12) + '[...]' + str.substr(str.length-12, str.length);
        }
        return str;
    };


    renderHtmlNavigation = () => {
        if(this.state.alignmentStarted){
            return <ul className="aligner-nav-log" role="navigation">
                <li>
                    <div id="logo"></div>
                    <div id="final_title">
                        {this.titleEllipsisCenter()}
                    </div>
                </li>
                <li>
                    <div id="source_to_target">
                        <span id="source"> en-US </span>
                        >
                        <span id="source"> it-IT </span>
                    </div>
                </li>
                <li>
                    <div id="mini_align_nav">
                        <p id="aligned"> Mismatch 1 </p> /  5
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
                        <div className="ui user-nolog label" title="Login">
                            <i className="icon user"></i>
                        </div>
                    </div>
                </li>
            </ul>
        } else {
            return <ul className="aligner-nav-nolog" role="navigation">
                <li id="logo"></li>
                <li id="user">
                    <div className="ui user-nolog label" title="Login">
                        <i className="icon-user22"></i>
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
}
export default HeaderComponent;