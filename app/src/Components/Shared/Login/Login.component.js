import React, {Component} from 'react';

class LoginComponent extends Component{
    constructor() {
        super();
    }

    render() {
        return (
                <div className="loginContainer">
                    <div className="header">
                        <div className="sx-header">
                            <img src="/public/img/logo-ico.png"></img>
                        </div>
                        <div className="dx-header"></div>
                    </div>
                    <div className="content">
                        <div className="ui grid my-grid">
                            <div className="row my-row">
                                <div className="ten wide column">
                                    <div className="dx-content"><h2>Sign up now to:</h2>
                                        <ul className="">
                                            <li><i className="icon check"></i>Manage your TMs, glossaries and MT engines</li>
                                            <li><i className="icon check"></i>Access the management panel</li>
                                            <li><i className="icon check"></i>Translate Google Drive files</li>
                                        </ul>
                                        <button className="ui button primary">Sign up</button>
                                    </div>
                                </div>
                                <div className="six wide column">
                                    <div className="sx-content">
                                        <div className="login-container-left">
                                            <button className="google-login">Sign in with Google</button>
                                            <div className="login-form-container">
                                                <div className="form-divider">
                                                    <div className="divider-line"></div>
                                                        <span>OR</span>
                                                    <div className="divider-line"></div>
                                                </div>
                                                <div>
                                                    <input
                                                        type="text"
                                                        placeholder="Email"
                                                        name="emailAddress"
                                                        tabIndex="1"
                                                        value="">
                                                    </input>
                                                </div>
                                                <div>
                                                    <input type="password"
                                                           placeholder="Password (minimum 8 characters)"
                                                           name="password"
                                                           tabIndex="2"
                                                           value="">
                                                    </input>
                                                </div>
                                                <button className="login-btn ui button primary disabled" tabIndex="3">
                                                    <span className="button-loader "></span> Sign in
                                                </button>
                                                <span className="forgot-password">Forgot password?</span>
                                            </div>
                                        </div>

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