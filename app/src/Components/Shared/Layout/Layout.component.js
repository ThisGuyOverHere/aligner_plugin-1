import React, {Component} from 'react';
import {Route} from 'react-router-dom'
import HeaderComponent from "../Header/Header.component";
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";
import SystemActions from "../../../Actions/System.actions";
import Authentication from "../Authentication/Authentication.component";

class Layout extends Component {
    constructor(props) {
        super(props);
        this.state = {
            user: false,
            googleUserImage: ''
        }
    }

    componentDidMount() {
        SystemActions.checkUserStatus();
        SystemStore.addListener(SystemConstants.USER_STATUS, this.userStatus);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.USER_STATUS, this.userStatus);
    }

    render = () => {
        const {component: Component, ...rest} = this.props;
        return <Route {...rest} render={matchProps => (
            <div className="DefaultLayout">
                <Authentication user = {this.state.user} image={this.state.googleUserImage} />
                <HeaderComponent image={this.state.googleUserImage} user = {this.state.user} {...rest} {...matchProps}/>
                <Component {...matchProps} />
                <div id="hiddenHtml"></div>
            </div>
        )}/>
    };

    userStatus = (status,fromLogin, image, error) => {
        if(status && fromLogin && !error){
            setTimeout(()=>{
                SystemActions.setLoginStatus(false);
            },0);
            this.setState({
                loginError: false
            })
        }
        if(error){
            this.setState({
                loginError: true
            })
        }
        this.setState({
            user: status,
            googleUserImage: image
        })
    };
}

export default Layout;
