import React, {Component} from 'react';
import PreAlignStatus from "./PreAlignStatus/PreAlignStatus.component";
import Animation from "./Animation/Animation.component";
import {httpGetAlignmentInfo, httpGetPullingInfo} from "../../HttpRequests/Alignment.http";
import SourceComponent from "./Source/Source.component";
import TargetComponent from "./Target/Trget.component";
import env from "../../Constants/Env.constants";

class AnalyseComponent extends Component {
    static propTypes = {};
    pullingId = null;

    constructor(props) {
        super(props);
        this.state = {
            job: {
                password: props.match.params.password,
                id: props.match.params.jobID
            },
            sourceLang: '',
            sourceLangFileName: '',
            totalSourceSegments: '',
            targetLang: '',
            targetLangFileName: '',
            totalTargetSegments: '',
            actualPhase: 0,
            phaseName: '',
            progress: 0
        };
    };

    componentDidMount() {
        // get job info
        httpGetAlignmentInfo(this.state.job.id, this.state.job.password)
            .then(
                response => {
                    const info = response.data;
                    this.setState({
                        sourceLang: info.source_lang,
                        sourceLangFileName: info.target_filename,
                        targetLang: info.target_lang,
                        targetLangFileName: info.target_filename,
                    });
                }
            ).catch(
            error => {
                console.log(error);
            }
        );
        // pulling
        this.pullingId = setInterval(this.pullingInfo, env.pullingCallInterval);

    };

    componentWillUnmount() {
        clearInterval(this.pullingId);
    }

    render() {
        return (
            <div className="container analyse">
                <div className="files-info">
                    <SourceComponent
                        sourceLang={this.state.sourceLang}
                        sourceLangFileName={this.state.sourceLangFileName}
                        totalSourceSegments={this.state.totalSourceSegments}
                    />
                    <TargetComponent
                        targetLang={this.state.targetLang}
                        targetLangFileName={this.state.targetLangFileName}
                        totalTargetSegments={this.state.totalTargetSegments}
                    />
                </div>

                <PreAlignStatus jobId={this.state.job.id}
                                jobPassword={this.props.match.params.password}
                                actualPhase = {this.state.actualPhase}
                                progress = {this.state.progress}
                />
                <Animation/>
            </div>
        );
    }

    pullingInfo = () => {
        // call pulling info api
        httpGetPullingInfo(this.state.job.id, this.state.job.password)
            .then(
                response => {
                    const data = response.data;
                    // update info
                    this.setState({
                        actualPhase: data.phase,
                        totalSourceSegments: data.source_segments,
                        totalTargetSegments: data.target_segments,
                        phaseName: data.phase_name,
                        progress: +data.progress
                    });
                }
            ).catch(
                error => {
                    console.log(error);
                }
        );
        //clear pulling interval
        if(this.state.actualPhase === 7){
            clearInterval(this.pullingId);
        }
    }
}

export default AnalyseComponent;
