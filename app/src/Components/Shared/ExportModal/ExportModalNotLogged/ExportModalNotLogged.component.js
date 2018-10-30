import React, {Component} from 'react';
import {emailValidator} from "../../../../Helpers/SystemUtils.helper";
import SystemActions from "../../../../Actions/System.actions";
import PropTypes from "prop-types";

class ExportModalNotLogged extends Component {

    static propTypes = {

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
        };
    }

    render() {
        return (
            <div>
                <h1>Please login to the service</h1>
                <h3>Use your credential or sign in with Google</h3>
                <div id="login">
                    <div className="sx-content">
                        <h2>Sign up now to:</h2>
                        <ul className="">
                            <li><i className="icon circle"></i>Manage your TMs, glossaries and MT engines</li>
                            <li><i className="icon circle"></i>Access the management panel</li>
                            <li><i className="icon circle"></i>Translate Google Drive files</li>
                        </ul>
                        <button className="ui button primary">Sign up</button>
                    </div>

                    <div className="dx-content">
                        <button className="google-login" value="" type="">
                    <span>
                        <i className="icon google"></i>
                    </span>
                            Sign in with Google
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
                                <p className="error" hidden={this.state.validEmail}>Please insert a valid
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
                            <button className="login-btn ui button" tabIndex="3" type="submit"
                                    disabled={!this.state.isValid}> Login
                            </button>
                            <p className="error" hidden={!this.props.error}> Login failed </p>
                        </form>
                    </div>


                </div>
            </div>

        );
    }

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

    login = (event) => {
        event.preventDefault();
        if (this.state.isValid) {
            SystemActions.login(this.state.userData);
        }
    };

}

export default ExportModalNotLogged;