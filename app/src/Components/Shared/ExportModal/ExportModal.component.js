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

                <div className="exportContainer">
                    <div className="header">
                        <div className="sx-header">
                            <img src="/public/img/logo-ico.png"></img>
                        </div>
                        <div className="dx-header">
                            <span onClick={this.onCloseExportModal}>
                                <i className="icon window close"></i>
                            </span>
                        </div>
                    </div>
                    <div className="content">



                    </div>
                </div>
            </div>
        );
    }
}

export default ExportModal;