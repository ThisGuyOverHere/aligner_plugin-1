import React, {Component} from 'react';
import {Route} from 'react-router-dom'
import HeaderComponent from "../Header/Header.component";
import FooterComponent from "../Footer/Footer.component";
import LoginComponent from "../Login/Login.component";
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";


class Layout extends Component {
    constructor(props) {
        super(props);
        this.state = {
            statusLogin: false
        }
    }

    componentDidMount() {
        SystemStore.addListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin)
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
    }

    render = () => {
        const {component: Component, ...rest} = this.props;
        return <Route {...rest} render={matchProps => (
            <div className="DefaultLayout">
                {this.state.statusLogin && <LoginComponent />}
                <HeaderComponent {...matchProps}/>
                <Component {...matchProps} />
                <FooterComponent {...matchProps}/>
            </div>
        )}/>
    };

    setStatusLogin = (status) => {
        console.log('ricevo');
        this.setState({
            statusLogin: status
        })
    }

}


/*const Layout = ({component: Component, ...rest}) => {

    return (
        <Route {...rest} render={matchProps => (
            <div className="DefaultLayout">
                <HeaderComponent {...matchProps}/>
                <Component {...matchProps} />
                <FooterComponent {...matchProps}/>
            </div>
        )} />
    )
};*/

export default Layout;