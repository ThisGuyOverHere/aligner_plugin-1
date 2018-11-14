import React, {Component} from 'react';
import PropTypes from "prop-types";
import {httpCreateTmx, httpSaveTmx, httpExportTmxCloud, httpExportTmxPrivate} from "../../../../HttpRequests/Tmx.http";

class ExportModalLogged extends Component {

    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
        setCompletedExport: PropTypes.func.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            cloudCheckBox: true,
            tmxList: [],
            selected: null,
            oldKey: null,
            newTmx: null,
            txmInLoad: false,
            exporting: false
        };
    }

    componentDidMount = () => {
        this.fetchTmx();
    };

    fetchTmx = async () => {
        this.setState({
            txmInLoad: true,
        });
        try {
            const res = await fetch('/plugins/aligner/tm/mine');
            const list = await res.json();
            this.setState({
                tmxList: list,
                selected: list[0] ? list[0] : null,
                txmInLoad: false,
            });
        } catch (e) {
            console.error(e)
        }
        return true;

    };

    componentWillUnmount = () => {

    };

    render() {
        let exportBtn = ['export-btn', 'ui', 'button'];
        if(this.state.exporting){
            exportBtn.push('loading');
        }
        return (
            <div id="logged">
                <h1>Export TMX in public cloud or private</h1>
                <h3>No empty segment or hidden row will be exported</h3>

                <div className="destinations">
                    <input type="radio" name="checkbox-option" id="checkbox-button-opt-two"
                           className="hide-checkbox"
                           checked={this.state.cloudCheckBox}
                           value={this.state.cloudCheckBox}
                           onChange={this.cloudHandler}/>
                    <label htmlFor="checkbox-button-opt-two">Help to improve the public cloud</label>

                    <input type="radio" name="checkbox-option" id="checkbox-button-opt-three"
                           className="hide-checkbox"
                           value={this.state.cloudCheckBox}
                           onChange={this.cloudHandler}/>
                    <label htmlFor="checkbox-button-opt-three">Send to private TMX</label>
                </div>

                {this.state.cloudCheckBox ?
                    <p> A copy of your TMX will be sent to our public memory helping us to improve
                        our collaborative translation algorithm.  </p>
                    :
                    <div>
                        <button className="ui button create" onClick={this.createMemory}>Create new TMX</button>
                        {this.state.newTmx ?
                            <div className="new-memory">
                                <div className={"icon-container"}>
                                    <i className={"icon circle"}/>
                                </div>
                                <form onSubmit={this.saveMemory}>
                                    <div className="form-container">
                                        <input type="text" tabIndex="4" onChange={this.handleInput}
                                               placeholder="Description of tmx"/>
                                        <p>{this.state.newTmx.key}</p>
                                    </div>
                                    <div className="btn-container">
                                        <div>
                                            <button type="submit" className="save ui button">Save</button>
                                        </div>
                                        <div>
                                            <button type="" className="cancel ui button" onClick={this.reverseAdd}>Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            : null}

                        <div className="memories">
                            {(this.state.tmxList.length > 0 && !this.state.txmInLoad) && this.renderMemories()}
                            {this.state.txmInLoad && this.renderMemoriesLoader()}
                        </div>
                    </div>
                }

                <button className={exportBtn.join(" ")} tabIndex="6" disabled={!(this.state.tmxList.length > 0) && !this.state.cloudCheckBox} type="" onClick={this.exportTmx}>
                    Export
                </button>
            </div>
        );
    }

    renderMemoriesLoader = () => {
        const n = 3;
        return [...Array(n)].map((e, i) => <div className="memory memory-in-loading" key={i}>
            <div className="radio-container">
                <div className="radio-loader"></div>
            </div>
            <div className="memory-info">
                <p className="info-sx-text-loader"></p>
                <p className="info-dx-text-loader"></p>
            </div>
        </div>);
    };

    renderMemories = () => {
        let memories = [];
        this.state.tmxList.map((element, index) => {
            const memory = <div className="memory" key={index}>
                <div className="radio-container">
                    <input type="radio" className="hidden" name="memory"
                           checked={element.key === this.state.selected.key}
                           onChange={ () => this.handleCheckRadio(index)}
                           value={index} tabIndex={index}/>
                    <label onClick={() => this.handleCheckRadio(index)} htmlFor="memory"><span></span></label>
                </div>
                <div className="memory-info">
                    <p>{element.name ? element.name : 'Private TM and Glossary'}</p>
                    <p>{element.key}</p>
                </div>
            </div>;
            memories.push(memory);
            return element;
        });
        return memories;
    };
    handleCheckRadio = (index) => {
        if(this.state.newTmx){
           this.reverseAdd();
            this.setState({
                selected: this.state.tmxList[index]
            });
        }else{
            this.setState({
                selected: this.state.tmxList[index]
            });
        }
    };
    createMemory = () => {
        httpCreateTmx().then((response) => {
            const tmx = {
                id: response.data.id,
                key: response.data.key,
                pass: response.data.pass,
                name: null
            };
            this.setState({
                newTmx: tmx,
                oldKey: this.state.selected,
                selected: tmx,
            });
        }, (error) => {

        })
    };
    saveMemory = (e) => {
        e.preventDefault();
        httpSaveTmx(this.state.newTmx.key, this.state.newTmx.name).then((response) => {
            if (response.data) {
                let list = this.state.tmxList;
                list.unshift(this.state.newTmx);
                this.setState({
                    tmxList: list,
                    newTmx: null,
                    selected: this.state.newTmx,
                });
            }
        }, (error) => {
            console.error(error)
        });

    };
    handleInput = (e) => {
        let newTmx = this.state.newTmx;
        newTmx.name = e.target.value;
        this.setState({
            newTmx: newTmx
        });
    };

    cloudHandler = () => {
        this.setState({
            cloudCheckBox: !this.state.cloudCheckBox,
        })
    };

    exportTmx = () => {
        this.setState({
            exporting: true
        });
        if(this.state.cloudCheckBox){
            console.log("public");
            httpExportTmxCloud().then(response => {
                this.setState({
                    exporting: false
                });
                this.props.setCompletedExport();
                console.log(response)
            }, error => {
                this.setState({
                    exporting: false
                });
            })
        } else {
            console.log("private");
            httpExportTmxPrivate(this.state.selected.key).then(response => {
                this.setState({
                    exporting: false
                });
                this.props.setCompletedExport();
                console.log(response)
            }, error => {
                this.setState({
                    exporting: false
                });
            })
        }
    };

    reverseAdd = () => {
        this.setState({
            newTmx: null,
            selected: this.state.oldKey,
            oldKey: null,

        })
    }


}

export default ExportModalLogged;
