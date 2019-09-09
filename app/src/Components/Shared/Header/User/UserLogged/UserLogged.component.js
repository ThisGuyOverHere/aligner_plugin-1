import React from 'react';
import SystemActions from "../../../../../Actions/System.actions";
import {getUserInitials} from "../../../../../Helpers/SystemUtils.helper";

const UserLogged = (props) => (
            <div>
                <div className="ui logged label" title="Login"
                     onClick={ () => SystemActions.setLogoutStatus(true)}
                >
                   {/* { props.image ?
                        <img src={props.image}/> : null
                    }*/}
                    {getUserInitials(props.user.first_name, props.user.last_name )}
                </div>
            </div>
        );

export default UserLogged;