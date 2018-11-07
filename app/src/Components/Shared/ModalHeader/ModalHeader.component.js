import {getUserInitials} from "../../../Helpers/SystemUtils.helper";
import React, {Component} from 'react';
import PropTypes from "prop-types";
import SystemActions from "../../../Actions/System.actions";
import ProjectActions from "../../../Actions/Project.actions";
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";

class ModalHeader extends Component {
    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
        modalName: PropTypes.string,
    };

    constructor(props) {
        super(props);
        this.state = {
            image: ''
        }
    }

    componentDidMount() {
        SystemActions.checkUserStatus();
        SystemStore.addListener(SystemConstants.USER_STATUS, this.userStatus);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.USER_STATUS, this.userStatus);
    }

    onCloseModal = ( ) => {
        switch(this.props.modalName) {
            case 'export':
                SystemActions.setExportModalStatus(false);
                break;
            case 'split':
                ProjectActions.openSegmentToSplit(false);
                break;
            case 'login':
                SystemActions.setLoginStatus(false);
                break;
            case 'registration':
                SystemActions.setRegistrationStatus(false);
                break;
            case 'reset-password':
                SystemActions.setResetPasswordStatus(false);
                break;
            case 'change-password':
                SystemActions.setChangePasswordStatus(false);
                break;
            case 'logout':
                SystemActions.setLogoutStatus(false);
                break;
        }
    };

    render() {
        return (!this.props.user ?
                <div id="modal-header">
                    <div className="sx-header">
                        <img src="/public/img/logo-ico.png"></img>
                    </div>
                    <div className={"user-profile"}>
                    </div>
                    <div className="dx-header">
                    <span onClick={this.onCloseModal}>
                        <i className="icon window close"></i>
                    </span>
                    </div>
                </div>

                : <div id="modal-header">
                    <div className="sx-header">
                        <img src="/public/img/logo-ico.png"></img>
                    </div>
                    <div className={"user-profile"}>
                        <div className="user-data">
                            <div className="ui logged label">
                                { this.state.image ?
                                    <img src={this.state.image}/> : null
                                }
                                {getUserInitials(this.props.user.first_name, this.props.user.last_name) }
                            </div>
                            <div className="info">
                                <h3> {this.props.user.first_name} </h3>
                                <p>  {this.props.user.email} </p>
                            </div>
                        </div>
                    </div>
                    <div className="dx-header">
                    <span onClick={this.onCloseModal}>
                        <i className="icon window close"></i>
                    </span>
                    </div>
                </div>
        );
    }

    userStatus = (status,fromLogin, image, error) => {
        this.setState({
            image: image
        })
    };
};

export default ModalHeader;