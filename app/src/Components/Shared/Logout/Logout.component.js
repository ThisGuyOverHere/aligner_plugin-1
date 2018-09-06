import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import PropTypes from "prop-types";

class LogoutComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            user: PropTypes.oneOfType([PropTypes.bool,PropTypes.object])
        }
    }

    render = () => {
        return (
            <div>
                <div className="overlay" onClick={this.onCloseLogout}>
                </div>
                <div className="logoutContainer">
                    <div className="header">
                        <div className="sx-header">
                            <img src="/public/img/logo-ico.png"></img>
                            <h1>Profile</h1>
                        </div>
                        <div className="dx-header">
                            <span onClick={this.onCloseLogout}>
                                <i className="icon window close"></i>
                            </span>
                        </div>
                    </div>
                    <div className="content">
                        <div className="user-info-form">
                            <div className="avatar-user">TU</div>
                            <div className="user-name">
                                <strong>{this.props.user.first_name + ' ' + this.props.user.last_name }</strong>
                                <span className="grey-txt">{this.props.user.email}</span>
                                <div className="link">
                                    <a onClick={this.logout}>Logout</a>
                                    <a>Reset Password</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        );
    };


    onCloseLogout = () => {
        SystemActions.setLogoutStatus(false);
    };

    openLoginModal = () => {
        SystemActions.setLoginStatus(true);
        this.onCloseLogout();
    };

    logout = () => {
        SystemActions.logout();
        this.onCloseLogout();
    }


}

export default LogoutComponent;