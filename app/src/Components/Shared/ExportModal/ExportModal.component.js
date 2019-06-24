import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import ExportModalNotLogged from "./ExportModalNotLogged/ExportModalNotLogged.component";
import PropTypes from "prop-types";
import ExportModalLogged from "./ExportModalLogged/ExportModalLogged.component";
import ExportModalSendMail from "./ExportModalSendEmail/ExportModalSendEmail.component";
import ExportModalCompleted from "./ExportModalCompleted/ExportModalCompleted.component";
import ModalHeader from "../ModalHeader/ModalHeader.component";
import {httpConfig} from "../../../HttpRequests/System.http";

class ExportModal extends Component {
    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
        image: PropTypes.string,
        misAlignedSegments:PropTypes.number,
        hideSegments:PropTypes.number
    };

    constructor(props) {
        super(props);
        this.state = {
            sendEmail: false,
            completed: false,
            googleLogInLink: ''
        }
    }

    componentDidMount() {
        this.getConfigs();
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
                    <ModalHeader user={this.props.user}  image={this.props.image} modalName={"export"}/>
                    <div className="content">
                        { this.props.user &&
                            <img id="cat" src={"/public/img/master_matecat.png"}/>
                         }

                        {this.renderComponent()}

                        {(!this.state.sendEmail && !this.state.completed && !this.props.user) &&
                        <div className={"send-email"}>
                            <button
                                onClick={this.sendEmailHandler}
                                className="sendEmail">
                                Just send me the TMX
                            </button>
                        </div>
                        }
                    </div>
                </div>
            </div>
        );
    }

    renderComponent = () => {
        const {hideSegments,misAlignedSegments} = this.props;

        let component;
        if (this.state.completed) {
            component = <ExportModalCompleted/>
        } else if (this.state.sendEmail) {
            component = <ExportModalSendMail setCompletedExport={this.setCompletedExport} user={this.props.user}
                                             misAlignedSegments={misAlignedSegments}
                                             hideSegments={hideSegments}
                                             sendEmailHandler={this.sendEmailHandler}/>;
        } else if (this.props.user) {
            component = <ExportModalLogged setCompletedExport={this.setCompletedExport} user={this.props.user}
                                           misAlignedSegments={misAlignedSegments}
                                           hideSegments={hideSegments}/>;
        } else {
            component = <ExportModalNotLogged
                googleLink={this.state.googleLogInLink}
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

    // to do: move on open of modals
    getConfigs = () => {
        httpConfig()
            .then(response => {
                this.setState({
                    googleLogInLink: response.data.authURL,
                });
            })
            .catch(error => {
                console.error(error);
            })
    };
}

export default ExportModal;
