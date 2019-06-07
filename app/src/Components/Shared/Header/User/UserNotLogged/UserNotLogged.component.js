import React from 'react';
import SystemActions from "../../../../../Actions/System.actions";

const UserNotLogged = () => (
    <div className="ui user-nolog label" title="Login"
         onClick={() => SystemActions.setLoginStatus(true)}>
        <i className="icon user"/>
    </div>
);

export default UserNotLogged;