import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import ModalHeader from "../ModalHeader/ModalHeader.component";
import {httpResetPassword} from "../../../HttpRequests/System.http";

class ResetPasswordModal extends Component {
    constructor(props) {
        super(props);
        this.state = {
            userData: {
                email: "",
                wanted_url: "",
            },
            sending: false,
            completed: false
        }
    }

    onCloseResetPassword = () => {
        SystemActions.setResetPasswordStatus(false);
    };

    onBackToLoginClick = () => {
        SystemActions.setResetPasswordStatus(false);
        SystemActions.setLoginStatus(true);
    };

    resetPassword = (event) => {
        event.preventDefault();
        this.state.userData.wanted_url = window.location.href;
        this.setState({
            sending: true,
        });
        httpResetPassword(this.state.userData)
            .then(response => {
                this.setState({
                    sending: false,
                    completed: true,
                });
            }).catch(error => {
                this.setState({
                    sending: false,
                    completed: false,
                });
        })
    };

    emailChange = (event) => {
        let userData = this.state.userData;
        const name = event.target.name;
        const value = event.target.value;
        userData[name] = value;

        this.setState({
            userData: userData
        });
    };

    render = () => {
        let resteButton = ['ui', 'button','reset-btn'];
        if(this.state.sending){
            resteButton.push('loading');
        }

        return (
            <div>
                <div className="overlay" onClick={this.onCloseResetPassword}>
                </div>
                <div className="resetContainer">
                    <ModalHeader modalName={"reset-password"}/>
                    {!this.state.completed ?
                        <div className="content">
                            <div className="resetPasswordForm">
                                <p>
                                    Enter the email address associated with your account and we'll send you
                                    the link to reset your password.
                                </p>
                                <form onSubmit={this.resetPassword}>
                                    <input type="email" placeholder="Email" required
                                           name="email"
                                           value={this.state.userData.email}
                                           onChange={this.emailChange}
                                           tabIndex="1">
                                    </input>
                                    <button className={resteButton.join(" ")} type="submit" tabIndex="2">
                                        Send
                                    </button>
                                </form>
                                <p onClick={this.onBackToLoginClick} className="forgot-password">Back to login</p>
                            </div>
                        </div>
                        :
                        <div className="content">
                            <div className="resetPasswordForm">
                                <p>
                                    We sent an email to <b>{this.state.userData.email}</b> Follow the instructions to create a new password.
                                </p>
                            </div>
                        </div>
                    }
                </div>
            </div>
        );
    }
}

export default ResetPasswordModal;