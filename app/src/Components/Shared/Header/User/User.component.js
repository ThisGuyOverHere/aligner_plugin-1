import React from 'react';
import UserNotLogged from "./UserNotLogged/UserNotLogged.component";
import UserLogged from "./UserLogged/UserLogged.component";

const User = (props) => (
    <div id="user">
        {props.user ? (
            <UserLogged image={props.image} user={props.user} />
        ) : (
            <UserNotLogged/>
        )}
    </div>
);

export default User;