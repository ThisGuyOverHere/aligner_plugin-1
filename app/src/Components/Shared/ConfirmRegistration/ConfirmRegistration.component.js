import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import PropTypes from "prop-types";


class ConfirmRegistrationComponent extends Component {

    static propTypes = {
        error: PropTypes.bool
    };

    constructor(props) {
        super(props);
        this.state = {
           email: ''
        }
    }

    render = () => {
        return (
            <div>
                <div className="overlay" onClick={this.onCloseConfirmRegistration}>
                </div>
                <div className="registration">
                    <div className="header">
                        <div className="sx-header">
                            <img src="/public/img/logo-ico.png"></img>
                        </div>
                        <div className="dx-header">
                            <span onClick={this.onCloseConfirmRegistration}>
                                <i className="icon window close"></i>
                            </span>
                        </div>
                    </div>
                    <div className="content-confirm">
                        <p>
                            To complete your registration please follow the instructions in the email we sent you to
                            usertest22@gmail.com.
                        </p>
                        <div className={"resend"}>
                            <p className={"forgot-password"} > Resend email</p>
                            <button className="login-btn ui button primary" tabIndex="3" type="submit">
                                <span className="button-loader" onClick={this.onCloseConfirmRegistration} ></span> Ok
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        );
    };


    onCloseConfirmRegistration = () => {
        SystemActions.setConfirmModalStatus(false);
    };

    sendConfirmationEmail = (event) => {
        event.preventDefault();
        SystemActions.sendConfirmationEmail(this.state.userData);
        // close modal reg and open confirm
    };
}

export default ConfirmRegistrationComponent;