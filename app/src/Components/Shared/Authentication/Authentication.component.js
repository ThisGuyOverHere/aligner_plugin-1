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
            registrationError: '',
            googleLogInLink: '',
            googleDriveLink: '',
            googleUserImage: ''
        }
    }

    componentDidMount() {
        this.getConfigs();
        SystemStore.addListener(SystemConstants.REGISTRATION_ERROR, this.setRegistrationError);
        SystemStore.addListener(SystemConstants.LOGOUT, this.setLogoutStatus);
        SystemStore.addListener(SystemConstants.OPEN_REGISTRATION_MODAL, this.setStatusRegistration);
        SystemStore.addListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
        SystemStore.addListener(SystemConstants.OPEN_RESET_PASSWORD_MODAL, this.setStatusResetPasswordModal);
        SystemStore.addListener(SystemConstants.OPEN_CHANGE_PASSWORD_MODAL, this.setStatusChangePasswordModal);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.REGISTRATION_ERROR, this.setRegistrationError);
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
                    error={this.state.registrationError}
                    googleLink={this.state.googleLogInLink}
                />}
                {this.state.statusResetPasswordModal && <ResetPasswordModal />}
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

    setRegistrationError = (status) => {
        this.setState({
            registrationError: status
        })
    };

    setStatusRegistration = (status) => {
        this.setState({
            statusRegistrationModal: status
        })
    };

    setStatusResetPasswordModal = (status) => {
        this.setState({
            statusResetPasswordModal: status
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

    // to do: move on open of modals
    getConfigs = () => {
        httpConfig()
            .then(response => {
                this.setState({
                    googleLogInLink: response.data.authURL,
                    googleDriveLink: response.data.gdriveAuthURL,
                });
            })
            .catch(error => {
                console.log(error);
            })
    };
}

export default Authentication;
