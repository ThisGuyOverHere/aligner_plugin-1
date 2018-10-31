import React, {Component} from 'react';
import SystemActions from "../../../../Actions/System.actions";
import PropTypes from "prop-types";
import UserNotLogged from "./UserNotLogged/UserNotLogged.component";
import UserLogged from "./UserLogged/UserLogged.component";

class User extends Component {

    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool,PropTypes.object]),
        image: PropTypes.string
    };

    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div id="user">
                {this.props.user ? (
                    <UserLogged image={this.props.image} user={this.props.user} />
                ) : (
                    <UserNotLogged user={this.props.user} />
                )}
            </div>
        );
    }

    openLogin = () =>{
        SystemActions.setLoginStatus(true)
    };

}
export default User;