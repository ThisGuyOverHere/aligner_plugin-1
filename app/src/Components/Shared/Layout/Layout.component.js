import React, {Component} from 'react';
import {Route} from 'react-router-dom'
import HeaderComponent from "../Header/Header.component";
import FooterComponent from "../Footer/Footer.component";
import LoginComponent from "../Login/Login.component";
import SystemConstants from "../../../Constants/System.constants";
import SystemStore from "../../../Stores/System.store";
import ExportModal from "../ExportModal/ExportModal.component";


class Layout extends Component {
    constructor(props) {
        super(props);
        this.state = {
            statusLogin: false,
            statusExportModal: false,
        }
    }

    componentDidMount() {
        SystemStore.addListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
        SystemStore.addListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.OPEN_LOGIN, this.setStatusLogin);
        SystemStore.removeListener(SystemConstants.OPEN_EXPORT_MODAL, this.setStatusExportModal);
    }

    render = () => {
        const {component: Component, ...rest} = this.props;
        return <Route {...rest} render={matchProps => (
            <div className="DefaultLayout">
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