import React, {Component} from 'react';
import {Route} from 'react-router-dom'
import HeaderComponent from "../Header/Header.component";
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";
import ExportModal from "../ExportModal/ExportModal.component";
import SystemActions from "../../../Actions/System.actions";;
import {httpConfig, httpLogout} from "../../../HttpRequests/System.http";
import Authentication from "../Authentication/Authentication.component";

class Layout extends Component {
    constructor(props) {
        super(props);
        this.state = {
            statusExportModal: false,
            user: false,
            googleLogInLink: '',
            googleDriveLink: '',
            googleUserImage: ''
        }
    }

    componentDidMount() {
        SystemActions.checkUserStatus();
        this.getConfigs();
        SystemStore.addListener(SystemConstants.USER_STATUS, this.userStatus);
        SystemStore.addListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.USER_STATUS, this.userStatus);
        SystemStore.removeListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
    }

    render = () => {
        const {component: Component, ...rest} = this.props;
        return <Route {...rest} render={matchProps => (
            <div className="DefaultLayout">
                <Authentication user = {this.state.user} image={this.state.googleUserImage} />
                {this.state.statusExportModal && <ExportModal user = {this.state.user}
                                                              googleLink={this.state.googleLogInLink}
                                                              error = {this.state.loginError}/>}
                <HeaderComponent image={this.state.googleUserImage} user = {this.state.user} {...rest} {...matchProps}/>
                <Component {...matchProps} />
                <div id="hiddenHtml"></div>
            </div>
        )}/>
    };

    setStatusExportModal = (status) => {
        this.setState({
            statusExportModal: status
        })
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

    // to do: move on open of modals
    getConfigs = () => {
        httpConfig()
            .then(response => {
                this.setState({
                    googleLogInLink: response.data.authURL,
                    googleDriveLink: response.data.gdriveAuthURL,
                });
            })
            .catch(error => {
                console.log(error);
            })
    };
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
