import {getUserInitials} from "../../../Helpers/SystemUtils.helper";
import React, {Component} from 'react';
import PropTypes from "prop-types";
import SystemActions from "../../../Actions/System.actions";

class ModalHeader extends Component {
    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
        modalName: PropTypes.string,
    };

    constructor(props) {
        super(props);
    }

    onCloseModal = ( ) => {
        console.log("close");
        switch(this.props.modalName) {
            case 'export':
                SystemActions.setExportModalStatus(false);
                break;
            case 'login':
                SystemActions.setLoginStatus(false);
                break;
            case 'registration':
                SystemActions.setRegistrationStatus(false);
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
};

export default ModalHeader;