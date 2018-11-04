import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import {emailValidator, googleLogin} from "../../../Helpers/SystemUtils.helper";
import PropTypes from "prop-types";
import ModalHeader from "../ModalHeader/ModalHeader.component";
import {httpMe, httpRegistration} from "../../../HttpRequests/System.http";
import SystemConstants from "../../../Constants/System.constants";


class RegistrationComponent extends Component {

    static propTypes = {
        error: PropTypes.string,
        googleLink : PropTypes.string

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
            registered: false,
        }
    }

    render = () => {
        let registrationButton = ['ui', 'primary', 'button','registration-btn'];
        if(this.state.registered){
            registrationButton.push('loading');
        }

        return (
            <div>
                <div className="overlay" onClick={this.onCloseRegistration}>
                </div>
                <div className="registration">
                    <ModalHeader modalName={"registration"}/>
                    <div className="content">
                        <div>
                            <div className="login-container">
                                <button className="google-login" onClick={() => googleLogin(this.props.googleLink)}>
                                    <span>
                                        <i className="google icon"></i>
                                    </span>
                                    Sign in with Google
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
                                        <input type="text" placeholder="Surname" required
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
                                        <label className={!this.state.checkbox ? "error-check" : null}>
                                            Accept <span> </span>
                                            <a href="" target="_blank" className="forgot-password">
                                                Terms and conditions
                                            </a>
                                        </label>
                                    </div>
                                    <div className="btn-container">
                                        <button className="back-to-login ui button primary"
                                                onClick={this.openLoginModal}>
                                            Already registred? Login
                                        </button>
                                        <button className={registrationButton.join(" ")} tabIndex="3"
                                                type="submit">
                                            <span className="button-loader"></span> Register Now
                                        </button>
                                    </div>
                                    <p className="registration-error"
                                       hidden={!this.props.error}> {this.props.error} </p>
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
        //SystemActions.registration(this.state.userData, this.state.userData["user[email]"]);
        this.setState({
            registered: true,
        });

        httpRegistration(this.state.userData)
            .then(response => {
                SystemActions.setRegistrationStatus(false);
                SystemActions.setConfirmModalStatus(true, this.state.userData["user[email]"]);
                //console.log(response);
                this.setState({
                    registered: false,
                });
            })
            .catch(error => {
                const err = error.response.data.error.message;
                //console.log(err);
                SystemActions.setRegistrationError(err);
                this.setState({
                    registered: false,
                });
            })
        // close modal reg and open confirm

    };
}

export default RegistrationComponent;