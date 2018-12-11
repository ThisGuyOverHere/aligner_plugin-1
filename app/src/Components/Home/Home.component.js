import React, {Component} from 'react';
import UploadComponent from "./Upload/Upload.component";
import ProjectActions from "../../Actions/Project.actions";

class HomeComponent extends Component {
    constructor() {
        super();
    }

    componentDidMount(){
        ProjectActions.emptyStore(true);
    }

    render() {
        return (
            <div className="homeComponent">
                <div className="visor">
                    <div className="visor-content">
                        <div className="ui container">
                            <div className="ui grid">
                                <h1>Matecat Aligner</h1>
                            </div>
                            <div className="ui grid">
                                <h2>Best way to create TMX from your files</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="subHeader">
                    <div className="ui container">
                        <div className="ui grid">
                            <h2 className="ui header">Upload your files to align them with MateCat</h2>
                        </div>
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
