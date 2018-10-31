import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import {emailValidator} from "../../../Helpers/SystemUtils.helper";
import PropTypes from "prop-types";

class LoginComponent extends Component {

    static propTypes = {
        error: PropTypes.bool,
        googleLink: PropTypes.string
    };

    constructor(props) {
        super(props);
        this.state = {
            userData: {
                email: '',
                password: '',
            },
            isValid: false,
            validEmail: true,
            validPassword: true,
        }
    }

    render = () => {
        return (
            <div>
                <div className="overlay" onClick={this.onCloseLogin}>
                </div>
                <div className="loginContainer">
                    <div className="header">
                        <div className="sx-header">
                            <img src="/public/img/logo-ico.png"></img>
                        </div>
                        <div className="dx-header">
                            <span onClick={this.onCloseLogin}>
                                <i className="icon window close"></i>
                            </span>
                        </div>
                    </div>
                    <div className="content">
                        <div className="sx-content"><h2>Sign up now to:</h2>
                            <ul className="">
                                <li><i className="icon check"></i>Manage your TMs, glossaries and MT<br></br> engines
                                </li>
                                <li><i className="icon check"></i>Access the management panel</li>
                                <li><i className="icon check"></i>Translate Google Drive files</li>
                            </ul>
                            <button className="ui button primary">Sign up</button>
                        </div>
                        <div className="dx-content">
                            <div className="login-container-left">
                                <button className="google-login" onClick={this.google_popup}>
                                    <i className="google icon"></i>
                                    <span>Sign in with Google</span>
                                </button>

                                <form className="login-form-container" onSubmit={this.login}>
                                    <div className="form-divider">
                                        <div className="divider-line"></div>
                                        <span>OR</span>
                                        <div className="divider-line"></div>
                                    </div>
                                    <div>
                                        <input type="text" placeholder="Email"
                                               name="email" tabIndex="1"
                                               onChange={this.handleInputChange}
                                               value={this.state.userData.email}>
                                        </input>
                                        <p className="error" hidden={this.state.validEmail}>Please insert a valida
                                            email.</p>
                                    </div>
                                    <div>
                                        <input type="password" placeholder="Password (minimum 8 characters)"
                                               name="password" tabIndex="2"
                                               onChange={this.handleInputChange}
                                               value={this.state.userData.password}>
                                        </input>
                                        <p className="error" hidden={this.state.validPassword}>Password must be at least
                                            of 8 characters.</p>
                                    </div>
                                    <button className="login-btn ui button primary" tabIndex="3" type="submit"
                                            disabled={!this.state.isValid}>
                                        <span className="button-loader"></span> Sign in
                                    </button>
                                    <p className="error" hidden={!this.props.error}> Login failed </p>
                                    <span className="forgot-password" onClick={this.openResetPasswordModal}>Forgot password?</span>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };


    onCloseLogin = () => {
        SystemActions.setLoginStatus(false);
    };

    openResetPasswordModal = () => {
        SystemActions.setResetPasswordStatus(true);
        this.onCloseLogin();
    };

    handleInputChange = (event) => {
        let userData = this.state.userData;
        const name = event.target.name;
        const value = event.target.value;
        userData[name] = value;

        this.setState({
            userData: userData
        });

        /* validators logic */
        if( emailValidator(this.state.userData.email) && this.state.userData.password.length >= 8 ){
            this.setState({
                isValid: true,
            })
        }

        if( !emailValidator(this.state.userData.email) && this.state.userData.email !== '' ){
            this.setState({
                validEmail: false
            })
        }else {
            this.setState({
                validEmail: true
            })
        }

        if( this.state.userData.password.length < 8 && this.state.userData.password !== '' ){
            this.setState({
                validPassword: false,
            })
        }else {
            this.setState({
                validPassword: true,
            })
        }
    };

    google_popup = () => {
        let url = this.props.googleLink;
        let newWindow = window.open( url, 'name', 'height=600,width=900' );
        if ( window.focus ) {
            newWindow.focus();
        }
        let interval = setInterval(function () {
            if (newWindow.closed) {
                SystemActions.checkUserStatus();
                SystemActions.setLoginStatus(false);
                clearInterval(interval);
            }
        }, 600);
    };

    login = (event) => {
        event.preventDefault();
        if (this.state.isValid) {
            SystemActions.login(this.state.userData);
        }
    };
}

export default LoginComponent;