import React, {Component} from 'react';
import SystemActions from "../../../../../Actions/System.actions";
import PropTypes from "prop-types";

class UserNotLogged extends Component {

    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool,PropTypes.object])
    };

    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div className="ui user-nolog label" title="Login" onClick={this.openLogin}>
                <i className="icon user"></i>
            </div>
        );
    }

    openLogin = () =>{
        SystemActions.setLoginStatus(true)
    };

}
export default UserNotLogged;