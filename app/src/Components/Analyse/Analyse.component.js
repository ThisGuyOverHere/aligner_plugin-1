import React, {Component} from 'react';
import PreAlignStatus from "./PreAlignStatus/PreAlignStatus.component";
import Animation from "./Animation/Animation.component";
import {httpGetAlignmentInfo} from "../../HttpRequests/Alignment.http";
import SourceComponent from "./Source/Source.component";
import TargetComponent from "./Target/Trget.component";

class AnalyseComponent extends Component {
    static propTypes = {};

    constructor(props) {
        super(props);
        this.state = {
            job: {
                password: props.match.params.password,
                id: props.match.params.jobID
            },
            progress: 30,
            sourceLang: '',
            sourceLangFileName: '',
            totalSourceSegments: '',
            targetLang: '',
            targetLangFileName: '',
            totalTargetSegments: ''
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
                        totalSourceSegments: info.total_source_segments,
                        targetLang: info.target_lang,
                        targetLangFileName: info.target_filename,
                        totalTargetSegments: info.total_target_segments
                    });
                }
            ).catch(
            error => {
                console.log(error);
            }
        );
    };

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

                <PreAlignStatus props={this.props}/>

                <Animation/>
            </div>
        );
    }
}

export default AnalyseComponent;
