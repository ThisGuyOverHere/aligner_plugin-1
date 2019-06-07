import React from 'react';
import SystemActions from "../../../Actions/System.actions";
import LoginComponent from "../Login/Login.component";
import ModalHeader from "../ModalHeader/ModalHeader.component";

const LoginModalComponent = (props) => (
    <div>
        <div className="overlay" onClick={() => SystemActions.setLoginStatus(false)}>
        </div>
        <div className="loginContainer">
            <ModalHeader user={props.user} modalName={"login"}/>
            <div className="content">
                <LoginComponent googleLink={props.googleLink}/>
            </div>
        </div>
    </div>
);

export default LoginModalComponent;