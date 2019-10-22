import React, {Component} from 'react';
import {Route} from 'react-router-dom'
import ReactGA from 'react-ga';
import HeaderComponent from "../Header/Header.component";
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";
import SystemActions from "../../../Actions/System.actions";
import Authentication from "../Authentication/Authentication.component";
import Env from "../../../Constants/Env.constants";

class Layout extends Component {
    constructor(props) {
        super(props);
        this.state = {
            user: false,
            googleUserImage: ''
        }
    }

    componentDidMount() {
        this.GAChangeRoute();
        SystemActions.checkUserStatus();
        SystemStore.addListener(SystemConstants.USER_STATUS, this.userStatus);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.USER_STATUS, this.userStatus);
    }

    shouldComponentUpdate(nextProps, nextState){
        if(nextProps.location.pathname !== this.props.location.pathname){
            this.GAChangeRoute(nextProps.location.pathname)
        }
        return true
    }

    render = () => {
        const {component: Component, ...rest} = this.props;
        return <Route {...rest} render={matchProps => (
            <div className="DefaultLayout">
                <Authentication user = {this.state.user} image={this.state.googleUserImage} />
                <HeaderComponent image={this.state.googleUserImage} user = {this.state.user} {...rest} {...matchProps}/>
                <Component {...matchProps} />
                <div id="hiddenHtml"/>
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

    GAChangeRoute = (pathname = null) =>{
        pathname = pathname ? pathname :  this.props.location.pathname;
        if(Env.GA_UA){
            ReactGA.set({page: location.pathname+ pathname});
            ReactGA.pageview(location.pathname+ pathname);
        }
    }
}

export default Layout;
