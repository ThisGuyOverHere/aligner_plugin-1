import React, {Component} from 'react';
import SystemActions from "../../../../Actions/System.actions";
import PropTypes from "prop-types";

class User extends Component {

    static propTypes = {

    };

    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div id="user">
                <div className="ui user-nolog label" title="Login" onClick={this.openLogin}>
                    <i className="icon user"></i>
                </div>
            </div>
        );
    }

    openLogin = () =>{
        SystemActions.setLoginStatus(true)
    };

}
export default User;