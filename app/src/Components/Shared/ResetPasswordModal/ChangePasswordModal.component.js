import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import ModalHeader from "../ModalHeader/ModalHeader.component";
import {httpChangePassword, httpResetPassword} from "../../../HttpRequests/System.http";

class ChangePasswordModal extends Component {
    constructor(props) {
        super(props);
        this.state = {
            userData: {
                password: "",
                password_confirmation: "",
            },
            changed: false,
            completed: false,
            valid: false,
        }
    }

    onCloseResetPassword = () => {
        SystemActions.setChangePasswordStatus(false);
    };

    changePassword = (event) => {
        event.preventDefault();
        this.setState({
            changed: true,
            valid: false,
        });
        if(this.state.userData.password_confirmation === this.state.userData.password){
            httpChangePassword(this.state.userData)
                .then(response => {
                    this.setState({
                        changed: false,
                        completed: true,
                    });
                }).catch(error => {
                this.setState({
                    changed: false,
                    completed: false,
                });
            })
        }else{
            this.setState({
                changed: false,
                valid: true,
            });
        }
    };

    inputChange = (event) => {
        let userData = this.state.userData;
        const name = event.target.name;
        const value = event.target.value;
        userData[name] = value;

        this.setState({
            userData: userData
        });
    };

    render = () => {
        let resetButton = ['ui', 'button','reset-btn'];
        if(this.state.changed){
            resetButton.push('loading');
        }

        return (
            <div>
                <div className="overlay" onClick={this.onCloseResetPassword}>
                </div>
                <div className="resetContainer">
                    <ModalHeader modalName={"change-password"}/>
                    {!this.state.completed ?
                        <div className="content">
                            <div className="resetPasswordForm">
                                <form onSubmit={this.changePassword}>
                                    <input type="password" placeholder="Password" required
                                           name="password"
                                           minLength={8}
                                           value={this.state.userData.password}
                                           onChange={this.inputChange}
                                           tabIndex="1">
                                    </input>
                                    <input type="password" placeholder="Confirm password" required
                                           minLength={8}
                                           name="password_confirmation"
                                           value={this.state.userData.password_confirmation}
                                           onChange={this.inputChange}
                                           tabIndex="2">
                                    </input>
                                    <button className={resetButton.join(" ")} type="submit" tabIndex="2">
                                        Send
                                    </button>
                                    { this.state.valid && <p className={"error"}> Password must be equal </p>}
                                </form>
                            </div>
                        </div>
                        :
                        <div className="content">
                            <div className="resetPasswordCompleted">
                                <p>
                                   Password has been changed successfully!
                                </p>
                            </div>
                        </div>
                    }
                </div>
            </div>
        );
    }
}

export default ChangePasswordModal;