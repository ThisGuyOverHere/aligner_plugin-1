import React, {Component} from 'react';
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";
import ResetPasswordModal from "../ResetPasswordModal/ResetPasswordModal.component";
import LogoutComponent from "../Logout/Logout.component";
import RegistrationComponent from "../Registration/Registration.component";
import {httpConfig, httpLogout} from "../../../HttpRequests/System.http";
import LoginModalComponent from "../LoginModal/LoginModal.component";
import PropTypes from "prop-types";
import ChangePasswordModal from "../ResetPasswordModal/ChangePasswordModal.component";
import SystemActions from "../../../Actions/System.actions";

class Authentication extends Component {

    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool,PropTypes.object]),
        image: PropTypes.string
    };

    constructor(props) {
        super(props);
        this.state = {
            statusLogin: false,
            statusResetPasswordModal: false,
            statusLogout: false,
            statusRegistrationModal: false,
            statusChangePasswordModal: false,
            user: false,
            loginError: false,
            newUserEmail: '',
            googleLogInLink: '',
            googleDriveLink: '',
            googleUserImage: '',
            fromExport: false
        }
    }

    componentDidMount() {
        this.getConfigs();
        SystemStore.addListener(SystemConstants.LOGOUT, this.setLogoutStatus);
        SystemStore.addListener(SystemConstants.OPEN_REGISTRATION_MODAL, this.setStatusRegistration);
        SystemStore.addListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
        SystemStore.addListener(SystemConstants.OPEN_RESET_PASSWORD_MODAL, this.setStatusResetPasswordModal);
        SystemStore.addListener(SystemConstants.OPEN_CHANGE_PASSWORD_MODAL, this.setStatusChangePasswordModal);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.LOGOUT, this.setLogoutStatus);
        SystemStore.removeListener(SystemConstants.OPEN_REGISTRATION_MODAL, this.setStatusRegistration);
        SystemStore.removeListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
        SystemStore.removeListener(SystemConstants.OPEN_RESET_PASSWORD_MODAL, this.setStatusResetPasswordModal);
        SystemStore.addListener(SystemConstants.OPEN_CHANGE_PASSWORD_MODAL, this.setStatusChangePasswordModal);
    }

    render = () => {
        return (
            <div className="AuthenticationLayout">
                {this.state.statusRegistrationModal && <RegistrationComponent
                    googleLink={this.state.googleLogInLink}
                    fromExport={this.state.fromExport}
                />}
                {this.state.statusResetPasswordModal && <ResetPasswordModal fromExport={this.state.fromExport} />}
                {this.state.statusChangePasswordModal && <ChangePasswordModal />}
                {this.state.statusLogin && < LoginModalComponent googleLink={this.state.googleLogInLink} />}
                {this.state.statusLogout && < LogoutComponent user = {this.props.user} image={this.props.image}/>}
            </div>
        )
    };

    setStatusLogin = (status) => {
        this.setState({
            statusLogin: status
        })
    };

    setStatusRegistration = (status,fromExport) => {
        this.setState({
            statusRegistrationModal: status,
            fromExport: fromExport
        })
    };

    setStatusResetPasswordModal = (status, fromExport) => {
        this.setState({
            statusResetPasswordModal: status,
            fromExport: fromExport
        })
    };

    setStatusChangePasswordModal = (status) => {
        this.setState({
            statusChangePasswordModal: status
        })
    };

    setLogoutStatus = (status) => {
        this.setState({
            statusLogout: status
        })
    };

    getConfigs = () => {
        httpConfig()
            .then(response => {
                this.setState({
                    googleLogInLink: response.data.authURL,
                    googleDriveLink: response.data.gdriveAuthURL,
                });
                if(response.data.forgot_password){
                    SystemActions.setChangePasswordStatus(true);
                }
            })
            .catch(error => {
                console.error(error);
            })
    };
}

export default Authentication;
