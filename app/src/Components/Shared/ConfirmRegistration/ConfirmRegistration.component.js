import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import PropTypes from "prop-types";
import ModalHeader from "../ModalHeader/ModalHeader.component";

class ConfirmRegistrationComponent extends Component {

    static propTypes = {
        email: PropTypes.string
    };

    constructor(props) {
        super(props);
        this.state = {
           email: this.props.email,
        }
    }

    render = () => {
        return (
            <div>
                <div className="overlay" onClick={this.onCloseConfirmRegistration}>
                </div>
                <div className="registration">
                    <ModalHeader modalName={"registration"}/>
                    <div className="content-confirm">
                        <p>
                            To complete your registration please follow the instructions in the email we sent you
                            {this.state.email}
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