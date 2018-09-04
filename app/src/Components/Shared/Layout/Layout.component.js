import React, {Component} from 'react';
import {Route} from 'react-router-dom'
import HeaderComponent from "../Header/Header.component";
import FooterComponent from "../Footer/Footer.component";
import LoginComponent from "../Login/Login.component";
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";
import ExportModal from "../ExportModal/ExportModal.component";
import ResetPasswordModal from "../ResetPasswordModal/ResetPasswordModal.component";

class Layout extends Component {
    constructor(props) {
        super(props);
        this.state = {
            statusLogin: false,
            statusExportModal: false,
            statusResetPasswordModal: false
        }
    }

    componentDidMount() {
        SystemStore.addListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
        SystemStore.addListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
        SystemStore.addListener(SystemConstants.OPEN_RESET_PASSWORD_MODAL, this.setStatusResetPasswordModal);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
        SystemStore.removeListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
        SystemStore.removeListener(SystemConstants.OPEN_RESET_PASSWORD_MODAL, this.setStatusResetPasswordModal);
    }

    render = () => {
        const {component: Component, ...rest} = this.props;
        return <Route {...rest} render={matchProps => (
            <div className="DefaultLayout">
                {this.state.statusResetPasswordModal && <ResetPasswordModal />}
                {this.state.statusLogin && <LoginComponent />}
                {this.state.statusExportModal && <ExportModal />}
                <HeaderComponent {...matchProps}/>
                <Component {...matchProps} />
                <FooterComponent {...matchProps}/>
            </div>
        )}/>
    };

    setStatusLogin = (status) => {
        this.setState({
            statusLogin: status
        })
    };

    setStatusExportModal = (status) => {
        this.setState({
            statusExportModal: status
        })
    };

    setStatusResetPasswordModal = (status) => {
        this.setState({
            statusResetPasswordModal: status
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