import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import ExportModalNotLogged from "./ExportModalNotLogged/ExportModalNotLogged.component";
import PropTypes from "prop-types";
import ExportModalLogged from "./ExportModalLogged/ExportModalLogged.component";
import ExportModalSendMail from "./ExportModalSendEmail/ExportModalSendEmail.component";
import ExportModalCompleted from "./ExportModalCompleted/ExportModalCompleted.component";
import {getUserInitials} from "../../../Helpers/SystemUtils.helper";
import ModalHeader from "../ModalHeader/ModalHeader.component";

class ExportModal extends Component {
    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
        error: PropTypes.bool,
        googleLink: PropTypes.string
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
                <div className="overlay" onClick={this.onCloseExportModal}>
                </div>

                <div className="exportContainer">
                    <ModalHeader user={this.props.user} modalName={"export"}/>
                    <div className="content">
                        { this.props.user &&
                            <img id="cat" src={"http://dev.matecat.com/public/img/matecat_watch-left-border.png"}/>
                         }

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
            component = <ExportModalNotLogged
                googleLink={this.props.googleLink}
                error={this.props.error}
                user={this.props.user}
            />;
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
