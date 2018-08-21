import React, {Component} from 'react';
import PropTypes from "prop-types";
import {Dropdown} from 'semantic-ui-react'
import Dropzone from 'react-dropzone'
import env from '../../Constants/Env.constants'
import {httpUpload} from '../../HttpRequests/Upload.http';
import {httpConversion, httpCreateProject, httpAlignJob} from "../../HttpRequests/Alignment.http";
import {Redirect} from "react-router";


class HomeComponent extends Component {
    constructor() {
        super();
        let languages = [];
        env.languages.map(e => {
            languages.push({
                key: e.code,
                text: e.value,
                value: e.code
            })
        });
        this.state = {
            languages: languages,
            job: undefined,
            pName: '',
            fileNameSource: null,
            fileNameTarget: null,
            sourceLang: 'en-US',
            targetLang: 'it-IT'
        }
    }


    onSourceLanguageChange = (e, value) => {
        this.setState({
            sourceLang: value.value
        })
    };

    onTargetLanguageChange = (e, value) => {
        this.setState({
            targetLang: value.value
        })
    };

    ProjectNameChange = (event) => {
        this.setState({pName: event.target.value});
    };

    onDropSource = (files) => {
        const onProgress = progressEvent => null;
        httpUpload(files[0], onProgress).then(response => {
            if (!response.errors) {
                httpConversion({
                    file_name: response.data.file.name,
                    source_lang: this.state.sourceLang,
                    target_lang: this.state.targetLang
                });
                this.setState({
                    fileNameSource: response.data.file.name
                });
            }
        });
    };


    onDropTarget = (files) => {
        const onProgress = progressEvent => null;
        httpUpload(files[0], onProgress).then(response => {

            if (!response.errors) {
                httpConversion({
                    file_name: response.data.file.name,
                    source_lang: this.state.targetLang,
                    target_lang: this.state.sourceLang
                });
                this.setState({
                    fileNameTarget: response.data.file.name
                });
            }
        });
    };
    startAlignment = () => {
        httpCreateProject({
            project_name: this.state.pName,
            file_name_source: this.state.fileNameSource,
            file_name_target: this.state.fileNameTarget,
            source_lang: this.state.sourceLang,
            target_lang: this.state.targetLang
        }).then(response => {

            this.setState({
                job: {
                    id: response.data.job.id,
                    password: response.data.job.password
                }
            });

            /* httpAlignJob(response.data.job.id).then(response => {
                 if(response.data){

                 }
                 console.log(response)
             })*/
        })
    };

    render() {

        const uploadAreaStyle = {};

        if (this.state.job) {
            return <Redirect push to={'/project/' + this.state.job.id + '/' + this.state.job.password}/>;
        }

        return (
            <div className="homeComponent">
                <div className="visor">
                    <div className="visor-content">

                    </div>
                </div>
                <div className="ui container">
                    <div id="uploader" className="ui grid">
                        <div className="row">
                            <div className="column">
                                <input className="form-control" type="text" value={this.state.pName} onChange={this.ProjectNameChange}/>
                            </div>
                        </div>
                        <div className="row">
                            <div className="four wide column">
                                <Dropdown fluid search selection
                                          options={this.state.languages}
                                          defaultValue={this.state.sourceLang}
                                          onChange={this.onSourceLanguageChange}
                                />
                            </div>
                            <div className="eight wide column">
                                <div className="dropzone">
                                    <Dropzone style={uploadAreaStyle} onDrop={this.onDropSource}>
                                        <p>Add source file (or drop it here).</p>
                                    </Dropzone>
                                </div>
                            </div>
                        </div>
                        <div className="row">
                            <div className="four wide column">
                                <Dropdown fluid search selection
                                          options={this.state.languages}
                                          defaultValue={this.state.targetLang}
                                          onChange={this.onTargetLanguageChange}
                                />
                            </div>
                            <div className="eight wide column">
                                <div className="dropzone">
                                    <Dropzone style={uploadAreaStyle} onDrop={this.onDropTarget}>
                                        <p>Add target file (or drop it here).</p>
                                    </Dropzone>
                                </div>
                            </div>
                        </div>
                        <div className="row">
                            <div className="sixteen wide column">
                                <button className="ui button" onClick={this.startAlignment}
                                        disabled={!this.state.fileNameSource || !this.state.fileNameTarget}
                                >Start alignment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

HomeComponent.propTypes = {};
export default HomeComponent;