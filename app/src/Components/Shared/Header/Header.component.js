import React, {Component} from 'react';
import { Link } from "react-router-dom";
class HeaderComponent extends Component {

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
            }
        }
    }

    titleEllipsisCenter = () => {
        let str = this.state.projectTitle;
        if(str.length > 30){
            return str.substr(0, 12) + '[...]' + str.substr(str.length-12, str.length);
        }
        return str;
    };

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
                        {this.titleEllipsisCenter()}
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
                        <div className="ui user-nolog label" title="Login">
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
                    <div className="ui user-nolog label" title="Login">
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
}
export default HeaderComponent;