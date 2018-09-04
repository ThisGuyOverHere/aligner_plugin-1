import React, {Component} from 'react';
import SystemActions from "../../../../Actions/System.actions";

class Export extends Component {

    static propTypes = {

    };

    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div id="export">
                <button className="ui primary button" onClick={this.openExportModal}>
                    <span>
                        Export
                        <i aria-hidden='true' className="upload icon"></i>
                    </span>
                </button>
            </div>
        );
    }

    openExportModal = () =>{
        SystemActions.setExportModalStatus(true)
    };
}
export default Export;