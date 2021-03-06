import React, {Component} from 'react';
import PropTypes from "prop-types";
import {Dropdown} from 'semantic-ui-react'
import Dropzone from 'react-dropzone'
import env from '../../../Constants/Env.constants'
import {httpUpload} from '../../../HttpRequests/Upload.http';
import {httpConversion, httpCreateProject, httpAlignJob} from "../../../HttpRequests/Alignment.http";
import {Redirect} from "react-router";


class UploadComponent extends Component {
    static propTypes = {};

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
            uploadSource: {
                progress: 0,
                start: false,
                status: 'start',
                name: null,
                size: 0
            },
            uploadTarget: {
                progress: 0,
                start: false,
                status: 'start',
                name: null,
                size: 0
            },
            sourceLang: 'en-US',
            targetLang: 'it-IT',
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
        const onProgress = progressEvent => {
            this.setState({
                uploadSource: {
                    progress: ((progressEvent.loaded * 100) / progressEvent.total),
                    start: true,
                    status: 'progress',
                    size: Math.floor((progressEvent.total) / 1000),
                    name: files[0].name
                }
            });
        };
        httpUpload(files[0], onProgress).then(response => {
            if (!response.errors) {
                httpConversion({
                    file_name: response.data[0].name,
                    source_lang: this.state.sourceLang,
                    target_lang: this.state.targetLang
                });
                this.setState({
                    uploadSource: {
                        status: 'finish',
                        progress: 0,
                        name: response.data[0].name,
                        size: Math.floor((files[0].size) / 1000)
                    }

                });
            }
        }, (error) => {
            this.setState({
                uploadSource: {
                    progress: 0,
                    status: 'error'
                }
            });
        });



    };


    onDropTarget = (files) => {
        const onProgress = progressEvent => {
            this.setState({
                uploadTarget: {
                    progress: ((progressEvent.loaded * 100) / progressEvent.total),
                    start: true,
                    status: 'progress',
                    size: Math.floor((progressEvent.total) / 1000),
                    name: files[0].name
                }
            })
        };
        httpUpload(files[0], onProgress).then(response => {

            if (!response.errors) {
                httpConversion({
                    file_name: response.data[0].name,
                    source_lang: this.state.targetLang,
                    target_lang: this.state.sourceLang
                });
                this.setState({
                    uploadTarget: {
                        start: false,
                        status: 'finish',
                        progress: 0,
                        name: response.data[0].name,
                        size: Math.floor((files[0].size) / 1000),
                    },

                });
            }
        }, (error) => {
            this.setState({
                uploadTarget: {
                    progress: 0,
                    status: 'error'
                }
            });
        });
    };

    startAlignment = () => {
        httpCreateProject({
            project_name: this.state.pName,
            file_name_source: this.state.uploadSource.name,
            file_name_target: this.state.uploadTarget.name,
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

    renderHtmlUpload = (status, data) =>{
        switch (status) {
            case 'start':
                return <p><span>+ Add Target file</span> (or drop it here).</p>;

            case 'progress':
                return <div>
                    <div className="fileInfo">
                        <p>
                            <i id="error-icon" aria-hidden='true' className='file icon'/> {data.filename}
                        </p>
                    </div>
                    <p id="fileSize"> {data.filesize} kb </p>
                    <div className='ui progress' data-percent={data.progress}>
                        <div className='bar' style={{width: data.progress + '%'}}/>
                    </div>
                    <p id="actionInfo">Uploading</p>
                </div>;

            case 'finish':
                return <p>
                    <i id="file-icon" aria-hidden='true' className='file icon'/>
                    <span className="fileInfo">{data.filename}</span>
                    <span id="fileSize"> {data.filesize} kb </span>
                    <i id="delete-icon" aria-hidden='true' className='trash alternate outline icon'/>
                </p>;

            case 'error':
                return <p>
                    <i id="error-icon" aria-hidden='true'
                       className='window close outline icon'/>Error during file upload : <span> Server problem occurred. </span>
                    <i id="delete-icon" aria-hidden='true'
                       className='trash alternate outline icon'/>
                    <i id="triangle" aria-hidden='true' className='triangle right icon'/>
                </p>;
        }
    };

    render() {

        const uploadAreaStyle = {};
        let classes = {
            source: ['dropzone'],
            target: ['dropzone']
        };

        classes.source.push(this.state.uploadSource.status);
        classes.target.push(this.state.uploadTarget.status);

        if (this.state.job) {
            return <Redirect push to={'/project/' + this.state.job.id + '/' + this.state.job.password}/>;
        }

        return (
            <div className="uploadComponent">
                <div className="uploadCard ui grid">
                    <div className="row" id="projectNameHeader">
                        <h3 className="ui header">Alignment project name <span>(optional)</span></h3>
                    </div>

                    <div className="row" id="projectNameInput">
                        <div className="thirteen wide column">
                            <div className="ui input">
                                <input className="form-control" name="pname" type="text" value={this.state.pName}
                                       onChange={this.ProjectNameChange}/>
                            </div>
                        </div>

                        <div className="three wide column">
                            <p>
                                <i aria-hidden='true' className='setting icon'/>
                                <span>Settings</span>
                            </p>
                        </div>
                    </div>

                    <div className="row">
                        <div className="six wide column">
                            <Dropdown fluid search selection
                                      options={this.state.languages}
                                      defaultValue={this.state.sourceLang}
                                      onChange={this.onSourceLanguageChange}
                            />
                        </div>
                        <div className="ten wide column">
                            <div className={classes.source.join(' ')}>
                                <Dropzone style={uploadAreaStyle} onDrop={this.onDropSource}>
                                    {
                                        this.renderHtmlUpload(
                                            this.state.uploadSource.status,
                                            {
                                                filename: this.state.uploadSource.name,
                                                progress: this.state.uploadSource.progress,
                                                filesize: this.state.uploadSource.size
                                            })
                                    }
                                </Dropzone>
                            </div>
                        </div>
                    </div>

                    <div className="row">
                        <div className="six wide column">
                            <Dropdown fluid search selection
                                      options={this.state.languages}
                                      defaultValue={this.state.targetLang}
                                      onChange={this.onTargetLanguageChange}
                            />
                        </div>
                        <div className="ten wide column">
                            <div className={classes.target.join(' ')}>
                                <Dropzone style={uploadAreaStyle} onDrop={this.onDropTarget}>
                                    {
                                        this.renderHtmlUpload(
                                            this.state.uploadTarget.status,
                                            {
                                                filename: this.state.uploadTarget.name ,
                                                progress: this.state.uploadTarget.progress,
                                                filesize: this.state.uploadTarget.size,
                                            })
                                    }
                                </Dropzone>
                            </div>
                        </div>
                    </div>
                    <div className="row" id="buttonRow">

                        <div className="twelve wide column">
                            <h4>MateCat supports <span> 71 file formats </span></h4>
                        </div>

                        <div className="four wide column">
                            <button className="ui primary button" onClick={this.startAlignment}
                                    disabled={!this.state.uploadSource.name || !this.state.uploadTarget.name}
                            >Start alignment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

export default UploadComponent;