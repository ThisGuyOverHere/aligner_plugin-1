import React, {Component} from 'react';
import {Route} from 'react-router-dom'
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";
import ResetPasswordModal from "../ResetPasswordModal/ResetPasswordModal.component";
import SystemActions from "../../../Actions/System.actions";
import LogoutComponent from "../Logout/Logout.component";
import RegistrationComponent from "../Registration/Registration.component";
import ConfirmRegistrationComponent from "../ConfirmRegistration/ConfirmRegistration.component";
import {httpConfig, httpLogout} from "../../../HttpRequests/System.http";
import LoginModalComponent from "../LoginModal/LoginModal.component";

class Authentication extends Component {
    constructor(props) {
        super(props);
        this.state = {
            statusLogin: false,
            statusResetPasswordModal: false,
            statusLogout: false,
            statusRegistrationModal: false,
            statusConfirmRegistrationModal: false,
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
        SystemActions.checkUserStatus();
        this.getConfigs();
        SystemStore.addListener(SystemConstants.USER_STATUS, this.userStatus);
        SystemStore.addListener(SystemConstants.REGISTRATION_ERROR, this.setRegistrationError);
        SystemStore.addListener(SystemConstants.LOGOUT, this.setLogoutStatus);
        SystemStore.addListener(SystemConstants.OPEN_REGISTRATION_MODAL, this.setStatusRegistration);
        SystemStore.addListener(SystemConstants.OPEN_CONFIRM_REGISTRATION_MODAL, this.setStatusRegistrationCompleted);
        SystemStore.addListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
        SystemStore.addListener(SystemConstants.OPEN_RESET_PASSWORD_MODAL, this.setStatusResetPasswordModal);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.USER_STATUS, this.userStatus);
        SystemStore.removeListener(SystemConstants.REGISTRATION_ERROR, this.setRegistrationError);
        SystemStore.removeListener(SystemConstants.LOGOUT, this.setLogoutStatus);
        SystemStore.removeListener(SystemConstants.OPEN_REGISTRATION_MODAL, this.setStatusRegistration);
        SystemStore.removeListener(SystemConstants.OPEN_CONFIRM_REGISTRATION_MODAL, this.setStatusRegistrationCompleted);
        SystemStore.removeListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
        SystemStore.removeListener(SystemConstants.OPEN_RESET_PASSWORD_MODAL, this.setStatusResetPasswordModal);
    }

    render = () => {
        return (
            <div className="AuthenticationLayout">
                {this.state.statusConfirmRegistrationModal && <ConfirmRegistrationComponent email={this.state.newUserEmail}/>}
                {this.state.statusRegistrationModal && <RegistrationComponent
                    error={this.state.registrationError}
                    googleLink={this.state.googleLogInLink}
                />}
                {this.state.statusResetPasswordModal && <ResetPasswordModal />}
                {this.state.statusLogin && < LoginModalComponent googleLink={this.state.googleLogInLink} error = {this.state.loginError}/>}
                {this.state.statusLogout && < LogoutComponent user = {this.state.user}/>}
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

    setStatusRegistrationCompleted = (status, email) => {
        this.setState({
            statusConfirmRegistrationModal: status,
            newUserEmail: email
        })
    };

    setStatusResetPasswordModal = (status) => {
        this.setState({
            statusResetPasswordModal: status
        })
    };

    setLogoutStatus = (status) => {
        this.setState({
            statusLogout: status
        })
    };

    userStatus = (status,fromLogin, image, error) => {
        if(status && fromLogin && !error){
            setTimeout(()=>{
                SystemActions.setLoginStatus(false);
            },0);
            this.setState({
                loginError: false
            })
        }
        if(error){
            this.setState({
                loginError: true
            })
        }
        this.setState({
            user: status,
            googleUserImage: image
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
