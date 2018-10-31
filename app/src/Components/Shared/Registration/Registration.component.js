import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import {emailValidator} from "../../../Helpers/SystemUtils.helper";
import PropTypes from "prop-types";


class RegistrationComponent extends Component {

    static propTypes = {
        error: PropTypes.string
    };

    constructor(props) {
        super(props);
        this.state = {
            userData: {
                'user[first_name]': '',
                'user[last_name]': '',
                'user[email]': '',
                'user[password]': '',
                'user[password_confirmation]': '',
                'user[wanted_url]': '',
            },
            isValid: false,
            checkbox: false,
            validEmail: true,
            validName: true,
            validSurname: true,
            validPassword: true,
        }
    }

    render = () => {
        return (
            <div>
                <div className="overlay" onClick={this.onCloseRegistration}>
                </div>
                <div className="registration">
                    <div className="header">
                        <div className="sx-header">
                            <img src="/public/img/logo-ico.png"></img>
                        </div>
                        <div className="dx-header">
                            <span onClick={this.onCloseRegistration}>
                                <i className="icon window close"></i>
                            </span>
                        </div>
                    </div>
                    <div className="content">
                        <div className="dx-content">
                            <div className="login-container-left">
                                <button className="google-login">
                                    <i className="google icon"></i>
                                    <span>Sign in with Google</span>
                                </button>
                                <form className="login-form-container" onSubmit={this.registration}>
                                    <div className="form-divider">
                                        <div className="divider-line"></div>
                                        <span>OR</span>
                                        <div className="divider-line"></div>
                                    </div>
                                    <h3>Register with your email</h3>
                                    <div>
                                        <input type="text" placeholder="Name" required
                                               name="user[first_name]" tabIndex="1"
                                               onChange={this.handleInputChange}
                                               value={this.state.userData["user[first_name]"]}>
                                        </input>
                                        <p className="error" hidden={this.state.validName}>Please insert a valid
                                            name.</p>
                                    </div>
                                    <div>
                                        <input type="text" placeholder="Surname"  required
                                               name="user[last_name]" tabIndex="1"
                                               onChange={this.handleInputChange}
                                               value={this.state.userData["user[last_name]"]}>
                                        </input>
                                        <p className="error" hidden={this.state.validSurname}>Please insert a valid
                                            surname.</p>
                                    </div>
                                    <div>
                                        <input type="email" placeholder="Email" required
                                               name="user[email]" tabIndex="1"
                                               onChange={this.handleInputChange}
                                               value={this.state.userData["user[email]"]}>
                                        </input>
                                        <p className="error" hidden={this.state.validEmail}>Please insert a valid
                                            email.</p>
                                    </div>
                                    <div>
                                        <input type="password" placeholder="Password (minimum 8 characters)"
                                               name="user[password]" tabIndex="2" required
                                               minLength="8"
                                               onChange={this.handleInputChange}
                                               value={this.state.userData["user[password]"]}>
                                        </input>
                                        <p className="error" hidden={this.state.validPassword}>Password must be at least
                                            of 8 characters.</p>
                                    </div>
                                    <div>
                                        <input type="checkbox" name="checkbox" tabIndex="2" required
                                               checked={this.state.checkbox}
                                               onChange={this.handleCheckChange}
                                        />
                                        <label>Accetta <a href="" className="forgot-password">Termini e
                                            condizioni</a></label>
                                        <p className="error-check " hidden={this.state.checkbox}>Please accept our
                                            terms.</p>
                                    </div>
                                    <button className="login-btn ui button primary" tabIndex="3" type="submit">
                                        <span className="button-loader"></span> Register Now
                                    </button>
                                    <p className="error" hidden={!this.props.error}> {this.props.error} </p>
                                    <span className="forgot-password" onClick={this.openLoginModal}>Already registred? Login</span>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    onCloseRegistration = () => {
        SystemActions.setRegistrationStatus(false);
    };

    openLoginModal = () => {
        SystemActions.setLoginStatus(true);
        this.onCloseRegistration();
    };

    handleInputChange = (event) => {
        let userData = this.state.userData;
        const name = event.target.name;
        const value = event.target.value;
        userData[name] = value;

        this.setState({
            userData: userData
        });

    };

    handleCheckChange = () => {
        this.setState({
            checkbox: !this.state.checkbox,
        });
    };

    registration = (event) => {
        //this.validation();
        event.preventDefault();

            this.state.userData["user[password_confirmation]"] = this.state.userData["user[password]"];
            this.state.userData["user[wanted_url]"] = window.location.href;
            SystemActions.registration(this.state.userData, this.state.userData["user[email]"]);
            // close modal reg and open confirm
        
    };
}

export default RegistrationComponent;