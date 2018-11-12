import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import PropTypes from "prop-types";
import {getUserInitials} from "../../../Helpers/SystemUtils.helper";
import ModalHeader from "../ModalHeader/ModalHeader.component";

class LogoutComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
            image: PropTypes.string
        }
    }

    render = () => {
        return (
            <div>
                <div className="overlay" onClick={this.onCloseLogout}>
                </div>
                <div className="logoutContainer">
                    <ModalHeader modalName={"logout"}/>
                    <div className="content">
                        <div className="user-info-form">
                            <div className="avatar-user">
                                { this.props.image ?
                                    <img src={this.props.image}/> : null
                                }
                                {getUserInitials(this.props.user.first_name, this.props.user.last_name)}
                            </div>
                            <div className="user-name">
                                <strong>{this.props.user.first_name + ' ' + this.props.user.last_name}</strong>
                                <span className="grey-txt">{this.props.user.email}</span>
                            </div>
                        </div>
                    </div>
                    <div className="link">
                        <button className={"reset-btn"} onClick={this.openChangePasswordModal}>Reset Password</button>
                        <button className={"logout-btn"} onClick={this.logout}>Logout</button>
                    </div>
                </div>
            </div>

        );
    };


    onCloseLogout = () => {
        SystemActions.setLogoutStatus(false);
    };

    openChangePasswordModal = () => {
        SystemActions.setChangePasswordStatus(true);
        this.onCloseLogout();
    };

    logout = () => {
        SystemActions.logout();
        this.onCloseLogout();
    }


}

export default LogoutComponent;