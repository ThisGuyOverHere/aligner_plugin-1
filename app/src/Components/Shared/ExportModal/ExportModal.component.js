import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import ExportModalNotLogged from "./ExportModalNotLogged/ExportModalNotLogged.component";
import PropTypes from "prop-types";
import ExportModalLogged from "./ExportModalLogged/ExportModalLogged.component";
import ExportModalSendMail from "./ExportModalSendEmail/ExportModalSendEmail.component";


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
                <div className="overlay" onClick={this.onCloseExportModal}>
                </div>

                <div className="exportContainer">
                    <div className="header">
                        <div className="sx-header">
                            <img src="/public/img/logo-ico.png"></img>
                        </div>
                        <div className="dx-header">
                            <span onClick={this.onCloseExportModal}>
                                <i className="icon window close"></i>
                            </span>
                        </div>
                    </div>
                    <div className="content">

                        {this.renderComponent()}

                        {(!this.state.sendEmail || !this.state.completed) &&
                        <button
                            onClick={this.sendEmailHandler}
                            className="sendEmail">
                            Send me an email
                        </button>
                        }
                    </div>
                </div>
            </div>
        );
    }

    renderComponent = () => {
        let component;
        if (this.state.completed) {

        } else if (this.state.sendEmail) {
            component = <ExportModalSendMail sendEmailHandler={this.sendEmailHandler}/>;
        } else if (this.props.user) {
            component = <ExportModalLogged user={this.props.user}/>;
        } else {
            component = <ExportModalNotLogged/>;
        }
        return component;
    };
    sendEmailHandler = () => {
        this.setState({
            sendEmail: !this.state.sendEmail
        })
    };
}

export default ExportModal;
