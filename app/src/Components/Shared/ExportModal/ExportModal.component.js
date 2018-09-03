import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";

class ExportModal extends Component {
    constructor(props) {
        super(props);
    }

    onCloseExportModal = () => {
        SystemActions.setExportModalStatus(false);
    };

    render() {
        return (
            <div>
                <div className="overlay" onClick={this.onCloseExportModal}>
                </div>

                <div className="loginContainer">
                    <div className="header">
                        <div className="sx-header">
                          
                        </div>
                        <div className="dx-header">
                            <span onClick={this.onCloseExportModal}>
                                <i className="icon window close"></i>
                            </span>
                        </div>
                    </div>
                    <div className="content">
                        <div className="sx-content">

                        </div>
                        <div className="dx-content">

                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

export default ExportModal;