import React, {Component} from 'react';
import PropTypes from 'prop-types';
import {httpExportTmxFile} from "../../../../HttpRequests/Tmx.http";

class ExportModalSendEmail extends Component {

    static propTypes = {
        sendEmailHandler: PropTypes.func.isRequired,
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
        setCompletedExport: PropTypes.func.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            cloudCheckBox: true,
            email: this.props.user ? this.props.user.email : '',
            sending: false
        };
    }

    render() {
        let sendBtn = ['send-btn', 'ui', 'button'];
        if (this.state.sending) {
            sendBtn.push('loading');
        }
        const validEmail = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/.test(this.state.email);
        return (
            <div id="sender">
                <h1> Download your TMX </h1>

                <div className="sender-content">
                    <a href="javascript:void(0);" onClick={this.props.sendEmailHandler}> &lt; Go back </a>
                    <p className={"description"}>A copy of your TMX will be sent to our collaborative memory shared with
                        all MateCat users to improve our alignment algorithm</p>
                    {(this.props.misAlignedSegments > 0 || this.props.hideSegments > 0) && <p className={"alert"}>
                        {this.props.misAlignedSegments > 0 && <span className={"evidence"}>{this.props.misAlignedSegments} Unaligned segments</span>}
                        {this.props.hideSegments > 0 && <span> and </span>}
                        {this.props.hideSegments > 0 && <span className={"evidence"}> {this.props.hideSegments} hidden segments</span>} will not be exported
                    </p>}
                    <div className={"btn-container"}>
                        <input type="email" tabIndex="0" placeholder="Enter your email address"
                               value={this.state.email}
                               onChange={this.inputHandler}/>
                        <button className={sendBtn.join(" ")} disabled={!validEmail} tabIndex="3" type="button"
                                onClick={this.exportTmx}>
                            Download
                        </button>
                    </div>
                    <p className={"small"}> We’ll send you an email when the download link is ready </p>
                </div>

            </div>
        );
    }

    inputHandler = (e) => {
        this.setState({
            email: e.target.value
        })
    };

    cloudHandler = () => {
        this.setState({
            cloudCheckBox: !this.state.cloudCheckBox,
        });
    };

    exportTmx = () => {
        this.setState({
            sending: true
        });
        httpExportTmxFile(this.state.email)
            .then(response => {
                this.setState({
                    sending: false
                });
                this.props.setCompletedExport();
            }, error => {
                this.setState({
                    sending: false
                });
            })
    }

}

export default ExportModalSendEmail;
