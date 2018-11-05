import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import PropTypes from "prop-types";
import LoginComponent from "../Login/Login.component";
import ModalHeader from "../ModalHeader/ModalHeader.component";

class LoginModalComponent extends Component {

    static propTypes = {
        googleLink: PropTypes.string
    };

    constructor(props) {
        super(props);
    }

    render = () => {
        return (
            <div>
                <div className="overlay" onClick={this.onCloseLogin}>
                </div>
                <div className="loginContainer">
                    <ModalHeader user={this.props.user} modalName={"login"}/>
                    <div className="content">
                        <LoginComponent googleLink={this.props.googleLink}/>
                    </div>
                </div>
            </div>
        );
    };

    onCloseLogin = () => {
        SystemActions.setLoginStatus(false);
    };

}

export default LoginModalComponent;