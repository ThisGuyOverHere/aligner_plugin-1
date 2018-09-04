import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";

class ResetPasswordModal extends Component {
    constructor(props) {
        super(props);
    }

    onCloseResetPassword = () => {
        SystemActions.setResetPasswordStatus(false);
    };

    onBackToLoginClick = () => {
        SystemActions.setResetPasswordStatus(false);
        SystemActions.setLoginStatus(true);
    };

    render = () => {
        return (
            <div>
                <div className="overlay" onClick={this.onCloseResetPassword}>
                </div>
                <div className="resetContainer">
                    <div className="header">
                        <div className="sx-header">
                            <img src="/public/img/logo-ico.png"></img>
                            <h1>Forgot password</h1>
                        </div>
                        <div className="dx-header">
                            <span onClick={this.onCloseResetPassword}>
                                <i className="icon window close"></i>
                            </span>
                        </div>
                    </div>
                    <div className="content">
                        <div className="resetPasswordForm">
                            <p>
                                Enter the email address associated with your account and we'll send you
                                the link to reset your password.
                            </p>
                            <div>
                                <input type="text" placeholder="Email"
                                       name="emailAddress"
                                       tabIndex="1" value="">
                                </input>
                            </div>
                            <button className="ui button primary" tabIndex="2">
                                <span className="button-loader "></span> Send
                            </button>
                            <br></br>
                            <span onClick={this.onBackToLoginClick} className="forgot-password">Back to login</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

export default ResetPasswordModal;