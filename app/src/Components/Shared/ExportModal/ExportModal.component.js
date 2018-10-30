import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import ExportModalNotLogged from "./ExportModalNotLogged/ExportModalNotLogged.component";
import PropTypes from "prop-types";
import ExportModalLogged from "./ExportModalLogged/ExportModalLogged.component";
import ExportModalSendMail from "./ExportModalSendEmail/ExportModalSendEmail.component";
import ExportModalCompleted from "./ExportModalCompleted/ExportModalCompleted.component";
import {getUserInitials} from "../../../Helpers/SystemUtils.helper";


class ExportModal extends Component {
    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
        error: PropTypes.bool,
    };

    constructor(props) {
        super(props);
        this.state = {
            sendEmail: false,
            completed: false
        }
    }

    onCloseExportModal = () => {
        SystemActions.setExportModalStatus(false);
    };

    render() {
        return (
            <div>
                <div className="overlay-b" onClick={this.onCloseExportModal}>
                </div>

                <div className="exportContainer">
                    {this.renderHeader()}
                    <div className="content">

                        {this.renderComponent()}

                        {(!this.state.sendEmail && !this.state.completed) &&
                        <div className={"send-email"}>
                            <button
                                onClick={this.sendEmailHandler}
                                className="sendEmail">
                                Do you want to download only the file?
                            </button>
                        </div>
                        }
                    </div>
                </div>
            </div>
        );
    }

    renderHeader = () => {
        let header;
        if (!this.props.user) {
            header = <div className="header">
                <div className="sx-header">
                    <img src="/public/img/logo-ico.png"></img>
                </div>
                <div className={"user-profile"}>
                </div>
                <div className="dx-header">
                    <span onClick={this.onCloseExportModal}>
                        <i className="icon window close"></i>
                    </span>
                </div>
            </div>

        } else {
            header = <div className="header">
                <div className="sx-header">
                    <img src="/public/img/logo-ico.png"></img>
                </div>
                <div className={"user-profile"}>
                    <div className="user-data">
                        <div className="ui logged label">
                            US
                            {/*getUserInitials(this.props.user.first_name, this.props.user.last_name) */}
                        </div>
                        <div className="info">
                            <h3> {this.props.user.first_name} </h3>
                            <p>  {this.props.user.email} </p>
                        </div>
                    </div>
                </div>
                <div className="dx-header">
                    <span onClick={this.onCloseExportModal}>
                        <i className="icon window close"></i>
                    </span>
                </div>
            </div>
        }
        return header;
    };

    renderComponent = () => {
        let component;
        if (this.state.completed) {
            component = <ExportModalCompleted/>
        } else if (this.state.sendEmail) {
            component = <ExportModalSendMail setCompletedExport={this.setCompletedExport} user={this.props.user}
                                             sendEmailHandler={this.sendEmailHandler}/>;
        } else if (this.props.user) {
            component = <ExportModalLogged setCompletedExport={this.setCompletedExport} user={this.props.user}/>;
        } else {
            component = <ExportModalNotLogged/>;
        }
        return component;
    };

    setCompletedExport = () => {
        this.setState({
            completed: true
        });
    };
    sendEmailHandler = () => {
        this.setState({
            sendEmail: !this.state.sendEmail
        })
    };
}

export default ExportModal;
