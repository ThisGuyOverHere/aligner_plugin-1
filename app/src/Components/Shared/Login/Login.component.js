import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";

class LoginComponent extends Component {
    constructor(props) {
        super(props);
    }

    onCloseLogin = () => {
        SystemActions.setLoginStatus(false);
    };

    openResetPasswordModal = () =>{
        SystemActions.setResetPasswordStatus(true);
        this.onCloseLogin();
    };

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
                                <button className="google-login">
                                    <i className="google icon"></i>
                                    <span>Sign in with Google</span>
                                </button>
                                <div className="login-form-container">
                                    <div className="form-divider">
                                        <div className="divider-line"></div>
                                        <span>OR</span>
                                        <div className="divider-line"></div>
                                    </div>
                                    <div><input type="text" placeholder="Email"
                                                name="emailAddress" tabIndex="1"></input>
                                    </div>
                                    <div>
                                        <input type="password" placeholder="Password (minimum 8 characters)"
                                               name="password" tabIndex="2"></input>
                                    </div>
                                    <button className="login-btn ui button primary disabled" tabIndex="3">
                                        <span className="button-loader "></span> Sign in
                                    </button>
                                    <span className="forgot-password" onClick={this.openResetPasswordModal}>Forgot password?</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        );
    }
}

export default LoginComponent;