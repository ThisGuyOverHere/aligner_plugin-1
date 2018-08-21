import React, {Component} from 'react';
import UploadComponent from "./Upload/Upload.component";

class HomeComponent extends Component {
    constructor() {
        super();
    }
    render() {
        return (
            <div className="homeComponent">
                <div className="visor">
                    <div className="visor-content">
                    </div>
                </div>
                <div className="ui container">
                    <UploadComponent/>
                </div>
            </div>
        );
    }
}

HomeComponent.propTypes = {};
export default HomeComponent;